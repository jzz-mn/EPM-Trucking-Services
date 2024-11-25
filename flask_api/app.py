from flask import Flask, jsonify, request
from flask_cors import CORS
from finance import FinanceAnalyzer
from maintenance import MaintenancePredictor
import mysql.connector
import pandas as pd
import logging

from sqlalchemy import create_engine

# Database configuration
DB_CONFIG = {
    'host': 'jazzmin12.mysql.pythonanywhere-services.com',
    'user': 'jazzmin12',
    'password': 'mysqladmin',  # Replace with your actual password
    'database': 'jazzmin12$epm'
}

# Create the database connection string
connection_string = (
    f"mysql+pymysql://{DB_CONFIG['user']}:{DB_CONFIG['password']}@"
    f"{DB_CONFIG['host']}/{DB_CONFIG['database']}"
)

# Create the SQLAlchemy engine
engine = create_engine(connection_string)

app = Flask(__name__)
CORS(app)

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)



def get_transaction_data():
    """Fetch transaction data from MySQL database"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        query = """
            SELECT 
                Date,
                TollFeeAmount,
                RateAmount,
                Amount,
                TotalKGs,
                FuelPrice
            FROM transactiongroup
            ORDER BY Date
        """
        
        # Read data into pandas DataFrame
        df = pd.read_sql(query, conn)
        conn.close()
        
        return df
        
    except Exception as e:
        logger.error(f"Database error: {str(e)}")
        raise

def get_expense_data():
    """Fetch expense and fuel data from MySQL database"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        
        # Fetch expenses data
        expenses_query = """
            SELECT 
                Date,
                SalaryAmount,
                MobileAmount,
                OtherAmount,
                (SalaryAmount + MobileAmount + OtherAmount) as TotalExpense
            FROM expenses
            ORDER BY Date
        """
        
        # Fetch fuel data
        fuel_query = """
            SELECT 
                Date,
                Liters,
                UnitPrice,
                Amount
            FROM fuel
            ORDER BY Date
        """
        
        # Read data into pandas DataFrames
        df_expenses = pd.read_sql(expenses_query, conn)
        df_fuel = pd.read_sql(fuel_query, conn)
        conn.close()
        
        # Merge expenses and fuel data
        df_combined = pd.merge(df_expenses, df_fuel, on='Date', how='outer')
        df_combined.fillna(0, inplace=True)
        
        # Calculate total expenses
        df_combined['Expenses'] = df_combined['TotalExpense'] + df_combined['Amount']
        
        return df_combined
        
    except Exception as e:
        logger.error(f"Database error: {str(e)}")
        raise

@app.route('/analyze/revenue', methods=['POST'])
def analyze_revenue():
    try:
        # Get data from database
        historical_data = get_transaction_data()
        
        if historical_data.empty:
            return jsonify({
                "status": "error",
                "message": "No transaction data available"
            }), 400

        # Initialize analyzer and make predictions
        analyzer = FinanceAnalyzer()
        analysis = analyzer.analyze_finances(historical_data)

        return jsonify({
            "status": "success",
            "analysis": analysis
        })

    except Exception as e:
        logger.error(f"Error during analysis: {str(e)}")
        return jsonify({
            "status": "error",
            "message": str(e)
        }), 500

@app.route('/predict_finance', methods=['POST'])
def predict_finance():
    try:
        # Get data from database
        historical_revenue = get_transaction_data()
        historical_expenses = get_expense_data()
        
        if historical_revenue.empty or historical_expenses.empty:
            return jsonify({
                "status": "error",
                "message": "No data available"
            }), 400

        # Rename columns before merging to avoid confusion
        historical_revenue = historical_revenue.rename(columns={
            'Amount': 'Revenue',
            'FuelPrice': 'FuelPrice_Revenue'
        })
        
        # Merge revenue and expenses data for profit calculation
        historical_data = historical_revenue.merge(
            historical_expenses, on='Date', how='outer'
        ).fillna(0)
        
        # Initialize analyzer
        analyzer = FinanceAnalyzer()
        
        # Train models
        if not analyzer.train_revenue_model(historical_revenue):
            raise Exception("Failed to train revenue model")
            
        if not analyzer.train_expenses_model(historical_expenses):
            raise Exception("Failed to train expenses model")
            
        if not analyzer.train_profit_model(historical_data):
            raise Exception("Failed to train profit model")

        # Make predictions
        last_date = historical_revenue['Date'].max()
        revenue_forecast = analyzer.predict_revenue(last_date)
        expense_forecast = analyzer.predict_expenses(last_date)
        profit_forecast = analyzer.predict_profit(last_date)

        if revenue_forecast is None or expense_forecast is None or profit_forecast is None:
            raise Exception("Failed to generate forecasts")

        # Format response
        forecast_data = []
        for rev, exp, prof in zip(
            revenue_forecast.to_dict('records'),
            expense_forecast.to_dict('records'),
            profit_forecast.to_dict('records')
        ):
            forecast_data.append({
                'month': rev['Date'],
                'revenue': float(rev['Revenue']),
                'expenses': float(exp['Expenses']),
                'profit': float(prof['Profit'])
            })

        return jsonify({
            "status": "success",
            "forecast": forecast_data,
            "metrics": {
                'revenue_mae': float(analyzer.metrics['revenue']['mae']),
                'expense_mae': float(analyzer.metrics['expenses']['mae']),
                'profit_mae': float(analyzer.metrics['profit']['mae'])
            },
            "forecast_period": {
                "start": revenue_forecast['Date'].min().strftime('%Y-%m-%d'),
                "end": revenue_forecast['Date'].max().strftime('%Y-%m-%d')
            }
        })

    except Exception as e:
        logger.error(f"Error during finance prediction: {str(e)}")
        logger.exception("Full traceback:")
        return jsonify({
            "status": "error",
            "message": str(e)
        }), 500

@app.route('/predict_maintenance', methods=['GET'])
def predict_maintenance():
    try:
        predictor = MaintenancePredictor()
        
        if not predictor.train_model():
            raise Exception("Failed to train maintenance model")
            
        predictions = predictor.predict_maintenance()
        
        if predictions is None:
            raise Exception("Failed to generate maintenance predictions")

        return jsonify({
            "status": "success",
            "metrics": predictor.metrics,
            "forecast": predictions
        })

    except Exception as e:
        logger.error(f"Error during maintenance prediction: {str(e)}")
        logger.exception("Full traceback:")
        return jsonify({
            "status": "error",
            "message": str(e)
        }), 500

@app.route('/get_truck_brand/<int:truck_id>', methods=['GET'])
def get_truck_brand(truck_id):
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)
        
        query = "SELECT Brand FROM trucks WHERE TruckID = %s"
        cursor.execute(query, (truck_id,))
        result = cursor.fetchone()
        
        conn.close()
        
        if result:
            return jsonify({"brand": result['Brand']})
        else:
            return jsonify({"brand": "Unknown"}), 404

    except Exception as e:
        logger.error(f"Error fetching truck brand: {str(e)}")
        return jsonify({
            "status": "error",
            "message": str(e)
        }), 500

# Add a test endpoint for database connection
@app.route('/test_db', methods=['GET'])
def test_db():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        conn.close()
        return jsonify({"status": "success", "message": "Database connection successful"})
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

@app.route('/evaluate_maintenance', methods=['GET'])
def evaluate_maintenance():
    try:
        predictor = MaintenancePredictor()
        logger.info("MaintenancePredictor initialized")
        
        if not predictor.train_model():
            logger.error("Model training failed")
            raise Exception("Failed to train maintenance model")
            
        evaluation = predictor.evaluate_model()
        
        if evaluation is None:
            logger.error("Model evaluation failed")
            raise Exception("Failed to evaluate maintenance model")

        logger.info("Evaluation successful")
        return jsonify({
            "status": "success",
            "evaluation": evaluation
        })

    except Exception as e:
        logger.error(f"Error during maintenance evaluation: {str(e)}")
        logger.exception("Full traceback:")
        return jsonify({
            "status": "error",
            "message": str(e)
        }), 500

if __name__ == "__main__":
    app.run(debug=True)
