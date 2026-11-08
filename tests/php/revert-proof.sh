#!/usr/bin/env bash
set -euo pipefail

if [ $# -ne 1 ]; then
  echo "Usage: $0 <mutation>" >&2
  echo "Mutations: install-default, customer-cache-id, upgrade-return, prices-drop-guard, empty-widget, version-reachability" >&2
  exit 1
fi

MUTATION="$1"
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DOCKER="${DOCKER:-docker}"
PHP_IMAGE="php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143"
PHPUNIT_VERSION="4.8.36"
PHPUNIT_SHA256="ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e"
SIGNING_FPR="D8406D0D82947747293778314AA394086372C20A"

EXPORT="$(mktemp -d)"
WORK="$(mktemp -d)"
GNUPGHOME="$(mktemp -d)"
chmod 0700 "$EXPORT" "$WORK" "$GNUPGHOME"
export GNUPGHOME
trap 'rm -rf "$EXPORT" "$WORK" "$GNUPGHOME"' EXIT

if ! command -v "$DOCKER" >/dev/null 2>&1; then
  echo "Docker not found in PATH (set DOCKER if needed)" >&2
  exit 1
fi

git archive HEAD | tar -x -C "$EXPORT"

apply_mutation() {
  case "$MUTATION" in
    install-default)
      TEST_FILE="/src/tests/php/InstallUninstallTest.php"
      TEST_FILTER='InstallUninstallTest::testSuccessfulInstallConfiguresAndRegistersHooks'
      perl -0pi -e "s/Configuration::updateValue\\('BLOCKSPECIALS_SPECIALS_NBR', 8\\)/Configuration::updateValue('BLOCKSPECIALS_SPECIALS_NBR', 9)/" \
        "$EXPORT/ps_specials.php"
      ;;
    customer-cache-id)
      TEST_FILE="/src/tests/php/CacheIdAndClearTest.php"
      TEST_FILTER='CacheIdAndClearTest::testCustomerIdIsAppendedAsIntegerSegment'
      perl -0pi -e 's/if \(!empty\(\$this->context->customer->id\)\) \{\s*\$cacheId \.= \x27\|\x27 \. \(int\) \$this->context->customer->id;\s*\}/if (false) {}/s' \
        "$EXPORT/ps_specials.php"
      ;;
    upgrade-return)
      TEST_FILE="/src/tests/php/Upgrade104Test.php"
      TEST_FILTER='Upgrade104Test::testRegistrationFailureMakesUpgradeFail'
      python3 - "$EXPORT/upgrade/upgrade-1.0.4.php" <<'PY'
import sys
from pathlib import Path
path = Path(sys.argv[1])
text = path.read_text()
old = """    return (bool) $module->registerHook(array(
        'actionObjectSpecificPriceAddAfter',
        'actionObjectSpecificPriceUpdateAfter',
        'actionObjectSpecificPriceDeleteAfter',
    ));"""
new = """    $module->registerHook(array(
        'actionObjectSpecificPriceAddAfter',
        'actionObjectSpecificPriceUpdateAfter',
        'actionObjectSpecificPriceDeleteAfter',
    ));

    return true;"""
if old not in text:
    raise SystemExit('upgrade return block not found')
path.write_text(text.replace(old, new, 1))
PY
      ;;
    prices-drop-guard)
      TEST_FILE="/src/tests/php/WidgetTest.php"
      TEST_FILTER='WidgetTest::testStringProductResultIsRejectedWithoutDiagnostics'
      python3 - "$EXPORT/ps_specials.php" <<'PY'
import sys
from pathlib import Path
path = Path(sys.argv[1])
text = path.read_text()
needle = "        if (is_array($products)) {"
insert = """        if (!is_array($products)) {
            return array(array('id_product' => 1));
        }
        if (is_array($products)) {"""
if needle not in text:
    raise SystemExit('is_array guard not found')
path.write_text(text.replace(needle, insert, 1))
PY
      ;;
    empty-widget)
      TEST_FILE="/src/tests/php/WidgetTest.php"
      TEST_FILTER='WidgetTest::testFalseProductResultReturnsExactFalseVariables'
      python3 - "$EXPORT/ps_specials.php" <<'PY'
import sys
from pathlib import Path
path = Path(sys.argv[1])
text = path.read_text()
old = """        if (!empty($products)) {
            return [
                'products' => $products,
                'allSpecialProductsLink' => Context::getContext()->link->getPageLink('prices-drop'),
            ];
        }

        return false;
    }

    private function getSpecialProducts()"""
new = old.replace("return false;", "return array();", 1)
if old not in text:
    raise SystemExit('getWidgetVariables block not found')
path.write_text(text.replace(old, new, 1))
PY
      ;;
    version-reachability)
      TEST_FILE="/src/tests/php/ConstructorTest.php"
      TEST_FILTER='ConstructorTest::testVersionMetadataMakesUpgrade104Reachable'
      perl -pi -e "s/1\\.0\\.4/1.0.3/g" "$EXPORT/ps_specials.php" "$EXPORT/config.xml"
      ;;
    *)
      echo "Unknown mutation: $MUTATION" >&2
      exit 1
      ;;
  esac
}

apply_mutation

for phpfile in "$EXPORT/ps_specials.php" "$EXPORT/upgrade/upgrade-1.0.4.php"; do
  if [ -f "$phpfile" ]; then
    "$DOCKER" run --rm --network none \
      -v "$EXPORT:/src:ro" \
      "$PHP_IMAGE" \
      php -l "/src${phpfile#$EXPORT}" >/dev/null
  fi
done

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

OUTPUT="$WORK/proof-output.txt"
set +e
"$DOCKER" run --rm --network none \
  -v "$EXPORT:/src:ro" \
  -v "$WORK:/work:rw" \
  -w /src \
  "$PHP_IMAGE" \
  php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 \
    "/work/phpunit-${PHPUNIT_VERSION}.phar" \
    --bootstrap /src/tests/php/bootstrap.php \
    --filter "$TEST_FILTER" \
    "$TEST_FILE" \
    --stderr >"$OUTPUT" 2>&1
STATUS=$?
set -e

cat "$OUTPUT"

if [ "$STATUS" -eq 0 ]; then
  echo "Proof failed: PHPUnit exited 0; expected a test failure" >&2
  exit 1
fi

if grep -q '^Usage: phpunit' "$OUTPUT"; then
  echo "Proof failed: PHPUnit usage output (missing test file?)" >&2
  exit 1
fi

if grep -qE '^OK \(' "$OUTPUT"; then
  echo "Proof failed: PHPUnit reported OK" >&2
  exit 1
fi

if grep -qE '^There (was|were) [0-9]+ errors?:' "$OUTPUT"; then
  echo "Proof failed: PHPUnit reported errors" >&2
  exit 1
fi

for prefix in "Parse error:" "Fatal error:" "Catchable fatal error:" "Recoverable fatal error:"; do
  if grep -q "$prefix" "$OUTPUT"; then
    echo "Proof failed: $prefix in output" >&2
    exit 1
  fi
done

if ! grep -qE '^Tests: [0-9]+, Assertions: [0-9]+, Failures: [1-9][0-9]*\.$' "$OUTPUT"; then
  echo "Proof failed: expected failure summary with non-zero Failures count" >&2
  exit 1
fi

echo "Proof passed for mutation $MUTATION ($TEST_FILTER)"
