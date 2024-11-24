# Create a new file called create_env.py in your flask_api folder
# Then run this script

content = """OPENAI_API_KEY=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
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=epm_database
GOOGLE_MAPS_API_KEY=your-google-maps-key"""

with open('.env', 'w', encoding='utf-8') as f:
    f.write(content)