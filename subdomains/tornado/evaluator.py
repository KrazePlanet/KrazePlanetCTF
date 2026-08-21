import sys, base64
from tornado.template import Template

def eval_tornado(template_str):
    try:
        t = Template(template_str)
        context = {
            'user': {
                'name': 'Elena Rostova',
                'email': 'elena.rostova@tornado-cloud.io',
                'role': 'Principal SRE',
                'team': 'Core Infrastructure Platform',
                'on_call': True
            },
            'node': {
                'hostname': 'edge-gw-08.us-east.prod',
                'region': 'us-east-1 (N. Virginia)',
                'ip': '10.240.18.94',
                'status': 'DEGRADED',
                'connections': 14280,
                'uptime': '99.995%'
            },
            'metric': {
                'throughput': '2.14M req/sec',
                'latency_p99': '142.5ms',
                'cpu_load': '89.4%',
                'error_rate': '4.12%'
            },
            'incident': {
                'id': 'INC-2026-8942',
                'severity': 'SEV-1 Critical',
                'summary': 'Edge Proxy Ingress Connection Pool Saturation',
                'timestamp': '2026-08-17 11:45:00 UTC'
            },
            'app_title': 'TornadoAsync Cloud Monitoring'
        }
        res = t.generate(**context)
        return res.decode('utf-8', errors='ignore')
    except Exception as e:
        return f"Tornado Template Render Error: {e}"

if __name__ == '__main__':
    b64_in = sys.argv[1] if len(sys.argv) > 1 else ''
    try:
        raw = base64.b64decode(b64_in).decode('utf-8')
        sys.stdout.write(eval_tornado(raw))
    except Exception as e:
        sys.stdout.write(f"Payload Decode Error: {e}")
