import contextlib
import io
import ipaddress
import pathlib
import types
import os
import subprocess
import tempfile
import shutil
import unittest
from unittest.mock import patch

script = pathlib.Path(__file__).resolve().parents[1].joinpath('instal.sh').read_text()
code = script.split("<<'PYDNS'\n", 1)[1].split('\nPYDNS\n', 1)[0]
dns = types.ModuleType('installer_dns')
exec(compile(code, 'instal.sh:PYDNS', 'exec'), dns.__dict__)
NETS = [ipaddress.ip_network('104.16.0.0/12'), ipaddress.ip_network('2606:4700::/32')]
IP = '13.185.135.57'
V6 = '2600:1f18:abcd::1'

class DNSValidation(unittest.TestCase):
    def check(self, a, aaaa=(), v6=()):
        return dns.evaluate(set(a), set(aaaa), IP, set(v6), NETS)

    def test_correct_ipv4(self):
        self.assertEqual(self.check([IP])[0], 'direto')

    def test_wrong_ipv4_and_extra_addresses_are_individually_identified(self):
        mode, messages = self.check([IP, '8.8.8.8', '8.8.4.4'])
        self.assertIsNone(mode)
        self.assertTrue(all(any(ip in m for m in messages) for ip in (IP, '8.8.8.8', '8.8.4.4')))

    def test_valid_and_invalid_ipv6(self):
        self.assertEqual(self.check([IP], [V6], [V6])[0], 'direto6')
        self.assertIsNone(self.check([IP], [V6], [])[0])
        self.assertIsNone(self.check([IP], [V6, '2001:4860::1'], [V6])[0])

    def test_cloudflare_and_mixed_proxy(self):
        self.assertEqual(self.check(['104.16.1.1', '104.16.1.2'], ['2606:4700::1'])[0], 'cloudflare')
        self.assertIsNone(self.check([IP, '104.16.1.1'])[0])

    def test_missing_a(self):
        self.assertIsNone(self.check([])[0])
        self.assertIsNone(self.check([], [V6], [V6])[0])

    def run_main(self, results, local=None, v6=None):
        def query(endpoint, domain, kind):
            name = 'cf' if 'cloudflare' in endpoint else 'google'
            value = results[(name, kind)]
            if isinstance(value, Exception):
                raise value
            return set(value)
        mock_socket = [(None, None, None, None, (a, 0)) for a in (local or [])]
        with patch.object(dns, 'query', side_effect=query), patch.object(dns, 'local_ipv6', return_value=set(v6 or [])), patch.object(dns.socket, 'getaddrinfo', return_value=mock_socket), patch('builtins.open', unittest.mock.mock_open(read_data='104.16.0.0/12\n2606:4700::/32\n')), contextlib.redirect_stdout(io.StringIO()) as out, contextlib.redirect_stderr(io.StringIO()) as err:
            status = dns.main('example.com', IP, '/ranges')
        return status, out.getvalue(), err.getvalue()

    def test_recent_change_divergent_invalid_cache(self):
        result = {('cf','A'):[IP], ('cf','AAAA'):[], ('google','A'):['8.8.8.8'], ('google','AAAA'):[]}
        status, _, err = self.run_main(result)
        self.assertEqual(status, 1)
        self.assertIn('8.8.8.8', err)

    def test_proxy_multiple_different_answers_are_accepted(self):
        result = {('cf','A'):['104.16.1.1'], ('cf','AAAA'):['2606:4700::1'], ('google','A'):['104.16.1.2'], ('google','AAAA'):['2606:4700::2']}
        status, out, _ = self.run_main(result)
        self.assertEqual((status, out.strip()), (0, 'cloudflare'))

    def test_absent_dns_and_failed_queries(self):
        result = {(r,k):[] for r in ('cf','google') for k in ('A','AAAA')}
        self.assertEqual(self.run_main(result)[0], 1)
        result = {(r,k):OSError('offline') for r in ('cf','google') for k in ('A','AAAA')}
        self.assertEqual(self.run_main(result)[0], 1)

    def test_recent_change_empty_answer_blocks(self):
        result = {('cf','A'):[IP], ('cf','AAAA'):[], ('google','A'):[], ('google','AAAA'):[]}
        self.assertEqual(self.run_main(result)[0], 1)

    def test_query_handles_nxdomain_as_negative_answer(self):
        response = subprocess.CompletedProcess([], 0, stdout='{"Status":3}', stderr='')
        with patch.object(dns.subprocess, 'run', return_value=response):
            self.assertEqual(dns.query('https://resolver.test', 'example.com', 'A'), set())

    def test_ipv6_enabled_if_one_resolver_has_valid_aaaa(self):
        result = {('cf','A'):[IP], ('cf','AAAA'):[V6], ('google','A'):[IP], ('google','AAAA'):[]}
        status, out, _ = self.run_main(result, v6=[V6])
        self.assertEqual((status, out.strip()), (0, 'direto6'))

    def test_local_cache_disagrees_but_public_correct(self):
        result = {(r,k):([IP] if k == 'A' else []) for r in ('cf','google') for k in ('A','AAAA')}
        status, out, err = self.run_main(result, local=['8.8.8.8'])
        self.assertEqual((status, out.strip()), (0, 'direto'))
        self.assertIn('resolvedor local difere', err)

class ShellIntegration(unittest.TestCase):
    def test_mode_ip_and_domain_normalization(self):
        preflight = script.split('[[ $dest == /*', 1)[0]
        preflight = preflight.replace('[[ $EUID == 0 ]]', '[[ 1 == 1 ]]')
        preflight = preflight.replace('[[ ${ID:-} == ubuntu && ${VERSION_ID:-} == 24.04 ]]', '[[ 1 == 1 ]]')
        preflight += "\nprintf 'RESULT:%s:%s:%s\\n' \"$mode\" \"$domain\" \"$url\"\n"
        with tempfile.TemporaryDirectory() as directory:
            path = pathlib.Path(directory) / 'preflight.sh'
            path.write_text(preflight)
            env = dict(os.environ, LINKFREE_NONINTERACTIVE='1')
            for arguments, expected in [
                (['--mode','ip','--public-ip',IP,'--database','sqlite'], 'RESULT:ip:' + IP + ':http://' + IP),
                (['--mode','domain','--domain','EXAMPLE.COM','--public-ip',IP,'--database','sqlite','--email','a@example.com'], 'RESULT:domain:example.com:https://example.com')]:
                result = subprocess.run(['bash', str(path), *arguments], env=env, text=True, capture_output=True)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn(expected, result.stdout)

    @unittest.skipUnless(shutil.which('nginx') and shutil.which('openssl'), 'nginx e openssl necessários')
    def test_generated_dual_stack_nginx_config(self):
        challenge = script.split(' cat > "$challenge_site" <<ACME\n', 1)[1].split('\nACME\n', 1)[0]
        redirect = script.split(' cat > "$site" <<NGINX\n', 1)[1].split('\nNGINX\n', 1)[0]
        site = script.split('cat >> "$site" <<NGINX\n', 1)[1].split('\nNGINX\n', 1)[0]
        with tempfile.TemporaryDirectory() as directory:
            cert, key = str(pathlib.Path(directory) / 'cert.pem'), str(pathlib.Path(directory) / 'key.pem')
            subprocess.run(['openssl','req','-x509','-newkey','rsa:2048','-nodes','-keyout',key,'-out',cert,'-days','1','-subj','/CN=example.com'], check=True, capture_output=True)
            env = dict(os.environ, domain='example.com', dest=directory, challenge_dir=directory,
                       ipv6_http='listen [::]:80;', listen_directive='listen 443 ssl; listen [::]:443 ssl;',
                       ssl_directives='ssl_certificate ' + cert + '; ssl_certificate_key ' + key + ';',
                       real_ip_config='', https_param='fastcgi_param HTTPS on;')
            for name, content in [('challenge', challenge), ('site', redirect + '\n' + site)]:
                # Heredoc expansion is identical to the one used by the installer.
                expanded = subprocess.run(['bash','-c','cat <<NGINX\n' + content + '\nNGINX'], env=env, text=True, capture_output=True, check=True).stdout
                config = pathlib.Path(directory) / (name + '.conf')
                config.write_text('pid ' + directory + '/nginx.pid; error_log stderr; events {} http { access_log off;\n' + expanded + '\n}\n')
                result = subprocess.run(['nginx','-t','-p',directory,'-c',str(config)], text=True, capture_output=True)
                self.assertEqual(result.returncode, 0, result.stderr)

    def test_ec2_metadata_and_external_fallback(self):
        function = script.split('detect_public_ip() {', 1)[1].split('\nvalid_ip()', 1)[0]
        shell = """set -eu
curl() { case \"$*\" in *api/token*) printf token;; *public-ipv4*) printf '13.185.135.57';; *api.ipify.org*) printf '8.8.8.8';; esac; }
detect_public_ip() {""" + function + "\ndetect_public_ip\n"
        result = subprocess.run(['bash', '-c', shell], text=True, capture_output=True)
        self.assertEqual(result.stdout, IP)
        self.assertIn('IMDSv2', result.stderr)
        shell = shell.replace("*api/token*) printf token", "*api/token*) :")
        result = subprocess.run(['bash', '-c', shell], text=True, capture_output=True)
        self.assertEqual(result.stdout, '8.8.8.8')
        self.assertIn('NAT', result.stderr)

    def test_cloudflare_ranges_with_no_final_newline(self):
        function = script.split('fetch_cloudflare_ranges() {', 1)[1].split('\nif [[ $dns_only', 1)[0]
        shell = """set -eu
fail() { echo \"$*\" >&2; exit 1; }
curl() { case \"${!#}\" in *ips-v4) printf '104.16.0.0/12';; *ips-v6) printf '2606:4700::/32';; esac; }
fetch_cloudflare_ranges() {""" + function + """
cf_file=$1
fetch_cloudflare_ranges
cat \"$cf_file\"
"""
        with tempfile.TemporaryDirectory() as directory:
            result = subprocess.run(['bash', '-c', shell, 'bash', str(pathlib.Path(directory) / 'ranges')], text=True, capture_output=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.splitlines(), ['104.16.0.0/12', '2606:4700::/32'])

if __name__ == '__main__':
    unittest.main()
