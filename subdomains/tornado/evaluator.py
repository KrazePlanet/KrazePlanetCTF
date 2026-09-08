import sys, base64
from tornado.template import Template

def eval_tornado(template_str):
    try:
        t = Template(template_str)
        context = {
            'wiki': {
                'space': 'DevOps & SRE Knowledge Base',
                'title': 'Production Disaster Recovery & Ingress Runbook',
                'category': 'Architecture & Runbooks',
                'version': 'v2.4.0',
                'last_updated': 'September 2026',
                'views': 4310
            },
            'author': {
                'name': 'Elena Rostova',
                'role': 'Lead Site Reliability Engineer',
                'email': 'elena.rostova@devwiki.internal',
                'team': 'Platform Resilience'
            },
            'system': {
                'engine': 'Tornado Template Engine 6.4',
                'node': 'wiki-worker-node-04',
                'region': 'eu-central-1'
            }
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
