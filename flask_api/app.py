from flask import Flask, request, jsonify
from flask_cors import CORS
import joblib
import pandas as pd
from sqlalchemy import create_engine, text
import logging
from datetime import datetime
import numpy as np

app = Flask(__name__)
CORS(app)

# Set up logging
logging.basicConfig(level=logging.DEBUG)
logger = logging.getLogger(__name__)

# Load the model
try:
    model = joblib.load('finance_model.joblib')
    logger.info("Model loaded successfully.")
except Exception as e:
    logger.error(f"Error loading model: {e}")
    model = None

# Database setup
#DATABASE_URI = 'mysql+pymysql://root:@localhost/epm_database'
DATABASE_URI = 'mysql://yomcgjllbxmlasdo:vksqeuuooyotal92@u0zbt18wwjva9e0v.cbetxkdyhwsb.us-east-1.rds.amazonaws.com:3306/kbapnyzlylrbyc47'
engine = create_engine(DATABASE_URI)

def get_historical_data():
    """Fetch historical revenue, expenses, and calculate profit dynamically from the database."""
    try:
        with engine.connect() as connection:
            query_revenue = text("""
                SELECT DATE_FORMAT(Date, '%Y-%m') AS month, 
                       SUM(Amount) AS revenue
                FROM transactiongroup
                GROUP BY DATE_FORMAT(Date, '%Y-%m')
                ORDER BY month ASC
            """)
            
            query_expenses = text("""
                SELECT DATE_FORMAT(Date, '%Y-%m') AS month, 
                       SUM(TotalExpense) AS expenses
                FROM expenses
                GROUP BY DATE_FORMAT(Date, '%Y-%m')
                ORDER BY month ASC
            """)
            
            revenue_result = connection.execute(query_revenue)
            expense_result = connection.execute(query_expenses)

            revenue_data = {row.month: float(row.revenue) for row in revenue_result}
            expense_data = {row.month: float(row.expenses) for row in expense_result}
            
            historical_data = []
            for month, revenue in revenue_data.items():
                expenses = expense_data.get(month, 0)
                profit = revenue - expenses
                historical_data.append({
                    'month': month,
                    'revenue': round(revenue, 2),
                    'expenses': round(expenses, 2),
                    'profit': round(profit, 2)
                })
            
            return sorted(historical_data, key=lambda x: x['month'])

    except Exception as e:
        logger.error(f"Database error: {e}")
        raise

@app.route("/predict_finance", methods=["POST"])
def predict_finance():
    try:
        if model is None:
            return jsonify({"error": "Model not loaded"}), 500

        data = request.get_json()
        months = int(data.get('months', 6))

        # Get historical data
        historical_data = get_historical_data()
        logger.debug(f"Historical data: {historical_data}")
        
        if not historical_data:
            return jsonify({"error": "No historical data available"}), 404

        # Extract revenue data for model features
        historical_revenues = np.array([float(entry['revenue']) for entry in historical_data])
        
        # Calculate future dates
        last_date = datetime.strptime(historical_data[-1]['month'], '%Y-%m')
        future_dates = pd.date_range(start=last_date, periods=months+1, freq='MS')[1:]
        
        # Initialize predictions array
        predictions = []
        
        # Create initial feature DataFrame
        current_features = pd.DataFrame({
            'Month': [future_dates[0].month],
            'Year': [future_dates[0].year],
            'Quarter': [future_dates[0].quarter],
            'Lag1': [historical_revenues[-1]],
            'Lag2': [historical_revenues[-2] if len(historical_revenues) > 1 else 0],
            'Lag3': [historical_revenues[-3] if len(historical_revenues) > 2 else 0]
        })
        
        # Calculate initial rolling mean
        current_features['RollingMean3'] = current_features[['Lag1', 'Lag2', 'Lag3']].mean(axis=1)

        # Make predictions one month at a time
        for i in range(months):
            # Make prediction for current month
            pred = float(model.predict(current_features)[0])
            
            # Ensure that the prediction is non-negative
            pred = max(0, pred)
            predictions.append(pred)
            
            # Prepare features for next month if needed
            if i < months - 1:
                next_date = future_dates[i + 1]
                current_features = pd.DataFrame({
                    'Month': [next_date.month],
                    'Year': [next_date.year],
                    'Quarter': [next_date.quarter],
                    'Lag1': [pred],
                    'Lag2': [current_features['Lag1'].iloc[0]],
                    'Lag3': [current_features['Lag2'].iloc[0]]
                })
                current_features['RollingMean3'] = current_features[['Lag1', 'Lag2', 'Lag3']].mean(axis=1)

        # Format forecast data
        forecast_data = [{
            "month": date.strftime("%Y-%m"),
            "predicted_revenue": round(pred, 2)
        } for date, pred in zip(future_dates, predictions)]

        return jsonify({
            "historical": historical_data,
            "forecast": forecast_data
        })

    except Exception as e:
        logger.error(f"Prediction error: {e}")
        return jsonify({"error": str(e)}), 500

# Add the predict_maintenance route below
@app.route('/predict_maintenance', methods=['POST'])
def predict_maintenance():
    data = request.get_json()
    # Process the data and return the response
    return jsonify({"maintenance_needed": True})  # Example response

