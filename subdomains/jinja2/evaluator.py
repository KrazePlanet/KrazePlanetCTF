import sys, base64
from jinja2 import Environment

def eval_jinja2(template_str):
    try:
        env = Environment()
        template = env.from_string(template_str)
        context = {
            'user': {
                'username': 'sophia_sec',
                'name': 'Sophia Chen',
                'first_name': 'Sophia',
                'last_name': 'Chen',
                'role': 'Principal Security Architect',
                'company': 'Nexus Cyber Systems',
                'email': 'sophia@nexus-cyber.internal',
                'location': 'Seattle, WA',
                'followers': 1420,
                'reputation': 8950,
                'skills': 'Python, Kubernetes, Cloud Security, DevSecOps',
                'joined': 'October 2023',
                'tier': 'Verified Staff Contributor'
            },
            'platform': {
                'name': 'DevSpace Profile Studio',
                'version': 'v4.2.1-prod',
                'environment': 'production'
            }
        }
        return template.render(**context)
    except Exception as e:
        return f"Jinja2 Template Render Error: {e}"

if __name__ == '__main__':
    b64_in = sys.argv[1] if len(sys.argv) > 1 else ''
    try:
        raw = base64.b64decode(b64_in).decode('utf-8')
        sys.stdout.write(eval_jinja2(raw))
    except Exception as e:
        sys.stdout.write(f"Payload Decode Error: {e}")
