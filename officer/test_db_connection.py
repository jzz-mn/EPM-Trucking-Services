# test_db_connection.py
from sqlalchemy import create_engine, text
import os
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

DATABASE_URI = os.getenv('DATABASE_URI')
if not DATABASE_URI:
    raise Exception("DATABASE_URI not set in environment variables.")

engine = create_engine(DATABASE_URI)

try:
    with engine.connect() as connection:
        # Use the text() construct for raw SQL queries
        result = connection.execute(text("SELECT 1"))
        print("Database connection successful:", result.fetchone())
except Exception as e:
    print("Database connection failed:", e)
