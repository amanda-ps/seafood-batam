import os
import re

root_path = 'c:/xampp/htdocs/SeafoodCode'

def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    new_content = content

    # 1. replace href="/...", src="/...", action="/..."
    # First pattern matches paths that have at least one char after the slash that is not a slash or quote
    new_content = re.sub(r'(href|src|action)\s*=\s*(["\'])/([^/"\'][^"\']*)(\2)', r'\1=\2<?= BASE_URL ?>/\3\4', new_content)
    
    # Second pattern matches exactly href="/"
    new_content = re.sub(r'(href|src|action)\s*=\s*(["\'])/(\2)', r'\1=\2<?= BASE_URL ?>/\3', new_content)
    
    # 2. replace active class logic in headers:
    new_content = re.sub(r'\$current_path\s*===\s*[\'"]/(.*?)[\'"]', r'basename($current_path) === \'\1\'', new_content)

    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {filepath}")

for root, dirs, files in os.walk(root_path):
    for file in files:
        if file.endswith('.php'):
            filepath = os.path.join(root, file)
            process_file(filepath)

print("Replacement complete.")
