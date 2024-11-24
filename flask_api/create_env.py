# Create a new file called create_env.py in your flask_api folder
# Then run this script

content = """OPENAI_API_KEY=your-api-key-goes-here
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=epm_database"""

with open('.env', 'w', encoding='utf-8') as f:
    f.write(content)