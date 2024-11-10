from flask import Flask, request, jsonify
import pandas as pd
import joblib
from datetime import datetime, timedelta
from sklearn.preprocessing import StandardScaler
from db_config import get_connection

# Load the trained model
model = joblib.load("finance_xgb_model.joblib")

app = Flask(__name__)

@app.route('/')
def home():
    return jsonify({"message": "Welcome to the Finance Analytics API!"})

@app.route('/forecast', methods=['GET'])
def forecast():
    try:
        months_ahead = int(request.args.get("months", 6))  # Default to a 6-month forecast
        conn = get_connection()
        query = """
            SELECT DATE_FORMAT(Date, '%Y-%m-01') AS Month, SUM(RateAmount + TollFeeAmount) AS MonthlyRevenue
            FROM transactiongroup
            GROUP BY Month
            ORDER BY Month
        """
        df = pd.read_sql(query, conn)
        conn.close()
        latest_data = df.iloc[-1:]

        forecasted_data = []
        current_date = datetime.strptime(latest_data['Month'].iloc[0], '%Y-%m-%d')

        for _ in range(months_ahead):
            current_date += timedelta(days=30)
            latest_data['Date'] = current_date.strftime('%Y-%m-%d')
            processed_df = StandardScaler().fit_transform(latest_data[['RateAmount', 'TollFeeAmount']])
            prediction = model.predict(processed_df)
            forecasted_data.append({
                'month': current_date.strftime('%Y-%m'),
                'predicted_revenue': prediction[0]
            })

        return jsonify(forecasted_data)
    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == "__main__":
    app.run(host='0.0.0.0', port=7860)
