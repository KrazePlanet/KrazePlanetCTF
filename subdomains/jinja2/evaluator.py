import sys, base64
from jinja2 import Environment

def eval_jinja2(template_str):
    try:
        env = Environment()
        template = env.from_string(template_str)
        context = {
            'user': {
                'name': 'Sophia Chen',
                'first_name': 'Sophia',
                'last_name': 'Chen',
                'email': 'sophia.chen@apex-security.internal',
                'role': 'Director of SecOps',
                'department': 'Cloud Architecture',
                'tier': 'Enterprise Elite'
            },
            'project': {
                'id': 'PRJ-2026-904',
                'name': 'CloudGuard Compliance Auditing Platform',
                'status': 'Deployment In Progress',
                'build_version': 'v4.18.2-release',
                'environment': 'Production (AWS us-west-2)'
            },
            'report': {
                'id': 'REP-89421',
                'security_score': '98.5%',
                'critical_vulns': 0,
                'compliance_status': 'SOC2 Type II Certified',
                'generated_at': '2026-08-17 11:50:00 UTC'
            },
            'company': {
                'name': 'CloudGuard Security & Compliance SaaS',
                'support_email': 'security-support@cloudguard.io',
                'portal_url': 'https://portal.cloudguard.io'
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
