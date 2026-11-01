#!/usr/bin/env python3
"""Run another disposable runtime against the already qualified exact ZIP."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--wordpress-version', required=True)
parser.add_argument('--php-version', required=True)
args = parser.parse_args()
if not re.fullmatch(r'\d+\.\d+\.\d+', args.wordpress_version) or not re.fullmatch(r'8\.[2-5]', args.php_version):
    parser.error('Use exact WordPress x.y.z and supported PHP 8.2 through 8.5 versions')
repo = Path(__file__).resolve().parents[1]
stage = repo / '.tmp/staged-release'
consumer = stage / 'wordpress'
receipt = json.loads((stage / 'qualification.json').read_text())
if receipt.get('kind') != 'staged-prepublication-only' or receipt.get('smoke_passed') is not True:
    raise RuntimeError('Qualify the staged ZIP before adding runtime receipts')
artifact = consumer / '.dist' / ('debugbundle-wordpress-' + receipt['wordpress_version'] + '.zip')
if hashlib.sha256(artifact.read_bytes()).hexdigest() != receipt['wordpress_sha256']:
    raise RuntimeError('Previously qualified ZIP identity changed')
# Refresh the test harness only. Installed plugin source still comes from the ZIP.
shutil.copytree(repo / 'scripts', consumer / 'scripts', dirs_exist_ok=True)
shutil.copy2(repo / 'docker-compose.smoke.yml', consumer / 'docker-compose.smoke.yml')
image = f'wordpress:{args.wordpress_version}-php{args.php_version}-apache'
cli_image = f'wordpress:cli-php{args.php_version}'
env = {**os.environ, 'DEBUGBUNDLE_USE_ASSEMBLED_ARTIFACT': '1', 'VERSION': receipt['wordpress_version'],
       'WORDPRESS_SMOKE_VERSION': args.wordpress_version, 'WORDPRESS_SMOKE_IMAGE': image,
       'WORDPRESS_SMOKE_CLI_IMAGE': cli_image}
env.pop('DEBUGBUNDLE_PHP_SDK_CHECKOUT', None)
subprocess.run(['./scripts/smoke-wordpress.sh'], cwd=consumer, env=env, check=True)
receipt['runtime'] = {'wordpress_version': args.wordpress_version, 'php_version': args.php_version,
                      'wordpress_image': image, 'wpcli_image': cli_image}
output = stage / f'qualification-wp{args.wordpress_version}-php{args.php_version}.json'
output.write_text(json.dumps(receipt, indent=2) + '\n')
print(f'Exact ZIP runtime qualification passed: {output}')
