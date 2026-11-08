#!/usr/bin/env bash
set -euo pipefail

if [ $# -ne 1 ]; then
  echo "Usage: $0 <mutation>" >&2
  exit 1
fi

MUTATION="$1"
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
EXPORT="$(mktemp -d)"
chmod 0700 "$EXPORT"
trap 'rm -rf "$EXPORT"' EXIT

git archive HEAD | tar -x -C "$EXPORT"

case "$MUTATION" in
  install-default)
    perl -0pi -e 's/Configuration::updateValue\(\x27BLOCKSPECIALS_SPECIALS_NBR\x27, 8\)/Configuration::updateValue(\x27BLOCKSPECIALS_SPECIALS_NBR\x27, 9)/' "$EXPORT/ps_specials.php"
    TEST_FILTER='InstallUninstallTest::testSuccessfulInstallConfiguresAndRegistersHooks'
    ;;
  customer-cache-id)
    perl -0pi -e 's/if \(!empty\(\$this->context->customer->id\)\) \{\s*\$cacheId \.= \x27\|\x27 \. \(int\) \$this->context->customer->id;\s*\}/if (false) {}/s' "$EXPORT/ps_specials.php"
    TEST_FILTER='CacheIdAndClearTest::testCustomerIdIsAppendedAsIntegerSegment'
    ;;
  upgrade-return)
    perl -0pi -e 's/return \(bool\) \$module->registerHook/return true; \/\/ \$module->registerHook/' "$EXPORT/upgrade/upgrade-1.0.4.php"
    TEST_FILTER='Upgrade104Test::testRegistrationFailureMakesUpgradeFail'
    ;;
  prices-drop-guard)
    perl -0pi -e 's/if \(is_array\(\$products\)\) \{/if (true) {/' "$EXPORT/ps_specials.php"
    TEST_FILTER='WidgetTest::testStringProductResultIsRejectedWithoutDiagnostics'
    ;;
  empty-widget)
    perl -0pi -e 's/return false;/return array();/' "$EXPORT/ps_specials.php"
    TEST_FILTER='WidgetTest::testFalseProductResultReturnsExactFalseVariables'
    ;;
  version-reachability)
    perl -pi -e "s/1.0.4/1.0.3/g" "$EXPORT/ps_specials.php" "$EXPORT/config.xml"
    TEST_FILTER='ConstructorTest::testVersionMetadataMakesUpgrade104Reachable'
    ;;
  *)
    echo "Unknown mutation: $MUTATION" >&2
    exit 1
    ;;
esac

PHP_IMAGE="php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143"
DOCKER="${DOCKER:-docker}"
WORK="$(mktemp -d)"
chmod 0700 "$WORK"
trap 'rm -rf "$EXPORT" "$WORK"' EXIT

"$ROOT/tests/php/run-tests.sh" >/dev/null || true
if [ ! -f "$WORK/phpunit-4.8.36.phar" ]; then
  # run-tests uses its own WORK; run single test via inline docker using export
  :
fi

# Reuse verified phar from a quick fetch in WORK
curl -fsSL -o "$WORK/phpunit-4.8.36.phar" "https://phar.phpunit.de/phpunit-4.8.36.phar"

OUTPUT="$WORK/proof-output.txt"
set +e
"$DOCKER" run --rm --network none \
  -v "$EXPORT:/src:ro" \
  -v "$WORK:/work:rw" \
  -w /src \
  "$PHP_IMAGE" \
  php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 \
    /work/phpunit-4.8.36.phar \
    --bootstrap /src/tests/php/bootstrap.php \
    --filter "$TEST_FILTER" --stderr >"$OUTPUT" 2>&1
STATUS=$?
set -e

cat "$OUTPUT"

if [ "$STATUS" -eq 0 ]; then
  echo "Proof failed: expected test $TEST_FILTER to fail" >&2
  exit 1
fi

for prefix in "Parse error:" "Fatal error:" "Test suite failed"; do
  if grep -q "$prefix" "$OUTPUT"; then
    echo "Proof failed: setup/parse error" >&2
    exit 1
  fi
done

echo "Proof passed for mutation $MUTATION"
