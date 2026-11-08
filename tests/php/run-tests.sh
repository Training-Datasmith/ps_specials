#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DOCKER="${DOCKER:-docker}"
PHP_IMAGE="php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143"
PHPUNIT_VERSION="4.8.36"
PHPUNIT_SHA256="ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e"
SIGNING_FPR="D8406D0D82947747293778314AA394086372C20A"
EXPECTED_TEST_FILES=(
  "/src/tests/php/CacheIdAndClearTest.php"
  "/src/tests/php/ConfigurationFormTest.php"
  "/src/tests/php/ConstructorTest.php"
  "/src/tests/php/DirectoryGuardTest.php"
  "/src/tests/php/InstallUninstallTest.php"
  "/src/tests/php/TemplateContractTest.php"
  "/src/tests/php/Upgrade104Test.php"
  "/src/tests/php/WidgetTest.php"
)
EXPECTED_TEST_COUNT=63
SUITE_NAME="module-tests"

WORK="$(mktemp -d)"
chmod 0700 "$WORK"
trap 'rm -rf "$WORK"' EXIT

GNUPGHOME="$(mktemp -d)"
chmod 0700 "$GNUPGHOME"
export GNUPGHOME
trap 'rm -rf "$WORK" "$GNUPGHOME"' EXIT

if ! command -v "$DOCKER" >/dev/null 2>&1; then
  echo "Docker not found in PATH (set DOCKER if needed)" >&2
  exit 1
fi

curl -fsSL -o "$WORK/phpunit-${PHPUNIT_VERSION}.phar" "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar"
curl -fsSL -o "$WORK/phpunit-${PHPUNIT_VERSION}.phar.asc" "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar.asc"
curl -fsSL -o "$WORK/gpg.asc" "https://sebastian-bergmann.de/gpg.asc"

gpg --batch --import "$WORK/gpg.asc" >/dev/null 2>&1
IMPORTED_FPR="$(gpg --with-colons --fingerprint "$SIGNING_FPR" 2>/dev/null | awk -F: '/^fpr:/ {print $10; exit}')"
if [ "$IMPORTED_FPR" != "$SIGNING_FPR" ]; then
  echo "Unexpected signing key fingerprint: $IMPORTED_FPR" >&2
  exit 1
fi

set +e
VERIFY_OUT="$(gpg --status-fd 1 --verify "$WORK/phpunit-${PHPUNIT_VERSION}.phar.asc" "$WORK/phpunit-${PHPUNIT_VERSION}.phar" 2>&1)"
set -e
echo "$VERIFY_OUT" | grep -q '\[GNUPG:\] VALIDSIG'
echo "$VERIFY_OUT" | grep "VALIDSIG" | awk '{print $NF}' | grep -q "$SIGNING_FPR"

ACTUAL_SHA256="$(sha256sum "$WORK/phpunit-${PHPUNIT_VERSION}.phar" | awk '{print $1}')"
if [ "$ACTUAL_SHA256" != "$PHPUNIT_SHA256" ]; then
  echo "PHPUnit PHAR SHA-256 mismatch: $ACTUAL_SHA256" >&2
  exit 1
fi

mapfile -t FOUND_FILES < <(find "$ROOT/tests/php" -maxdepth 1 -name '*Test.php' | sort)
EXPECTED_SORTED=(
  "$ROOT/tests/php/CacheIdAndClearTest.php"
  "$ROOT/tests/php/ConfigurationFormTest.php"
  "$ROOT/tests/php/ConstructorTest.php"
  "$ROOT/tests/php/DirectoryGuardTest.php"
  "$ROOT/tests/php/InstallUninstallTest.php"
  "$ROOT/tests/php/TemplateContractTest.php"
  "$ROOT/tests/php/Upgrade104Test.php"
  "$ROOT/tests/php/WidgetTest.php"
)
if [ "$(printf '%s\n' "${EXPECTED_SORTED[@]}")" != "$(printf '%s\n' "${FOUND_FILES[@]}")" ]; then
  echo "Test file list drift detected" >&2
  printf '%s\n' "${FOUND_FILES[@]}" >&2
  exit 1
fi

LIST="$(printf '%s\n' "${EXPECTED_TEST_FILES[@]}")"
export LIST
"$DOCKER" run --rm --network none \
  -e LIST \
  -v "$ROOT:/src:ro" \
  -v "$WORK:/work:rw" \
  -w /work \
  "$PHP_IMAGE" \
  php -r '
$files = explode("\n", trim(getenv("LIST")));
srand(20261108);
shuffle($files);
file_put_contents("/work/shuffled.txt", implode("\n", $files));
'
mapfile -t RUN_FILES < "$WORK/shuffled.txt"

XML="$WORK/phpunit.xml"
{
  echo '<?xml version="1.0" encoding="UTF-8"?>'
  echo '<phpunit bootstrap="/src/tests/php/bootstrap.php" colors="true"'
  echo '         convertErrorsToExceptions="true" convertNoticesToExceptions="true"'
  echo '         convertWarningsToExceptions="true" stopOnFailure="false">'
  echo "  <testsuites><testsuite name=\"$SUITE_NAME\">"
  for f in "${RUN_FILES[@]}"; do
    echo "    <file>$f</file>"
  done
  echo '  </testsuite></testsuites>'
  echo '</phpunit>'
} > "$XML"

PHP_VERSION_OUT="$("$DOCKER" run --rm --network none "$PHP_IMAGE" php -r 'echo PHP_VERSION;')"
if [ "$PHP_VERSION_OUT" != "5.6.40" ]; then
  echo "Unexpected PHP version: $PHP_VERSION_OUT" >&2
  exit 1
fi

OUTPUT="$WORK/phpunit-output.txt"
set +e
"$DOCKER" run --rm --network none \
  -v "$ROOT:/src:ro" \
  -v "$WORK:/work:rw" \
  -w /src \
  "$PHP_IMAGE" \
  php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 \
    /work/phpunit-"${PHPUNIT_VERSION}".phar \
    --configuration /work/phpunit.xml \
    --log-junit /work/junit.xml \
    --stderr >"$OUTPUT" 2>&1
STATUS=$?
set -e

cat "$OUTPUT"

if [ "$STATUS" -ne 0 ]; then
  echo "PHPUnit exited with status $STATUS" >&2
  exit "$STATUS"
fi

if ! grep -qE "^OK \\(${EXPECTED_TEST_COUNT} tests, [0-9]+ assertions\\)\$" "$OUTPUT"; then
  echo "Unexpected PHPUnit summary (expected OK (${EXPECTED_TEST_COUNT} tests, N assertions))" >&2
  exit 1
fi

for prefix in "Notice:" "Warning:" "Deprecated:" "Strict Standards:" "Parse error:" "Fatal error:"; do
  if grep -q "$prefix" "$OUTPUT"; then
    echo "PHP diagnostic found in output: $prefix" >&2
    exit 1
  fi
done

echo "Suite passed (${EXPECTED_TEST_COUNT} tests)."
