# Required imports
import matplotlib
matplotlib.use('Agg')  # Use a non-interactive backend

import pandas as pd
import numpy as np
from sqlalchemy import create_engine
import xgboost as xgb
from sklearn.model_selection import train_test_split
from sklearn.metrics import r2_score, mean_absolute_error, mean_absolute_percentage_error
import joblib

# Database connection using SQLAlchemy
DATABASE_URI = 'mysql://p7apqmgbef3tu2d6:qi0il5aezrqpsji6@ixnzh1cxch6rtdrx.cbetxkdyhwsb.us-east-1.rds.amazonaws.com:3306/lnm4m0erp17734x3'
#DATABASE_URI = 'mysql://yomcgjllbxmlasdo:vksqeuuooyotal92@u0zbt18wwjva9e0v.cbetxkdyhwsb.us-east-1.rds.amazonaws.com:3306/kbapnyzlylrbyc47'
engine = create_engine(DATABASE_URI)

# Fetch data from the `transactiongroup` table
query = "SELECT * FROM transactiongroup"
transactiongroup_data = pd.read_sql(query, engine)

# Ensure the 'Date' column is in datetime format
transactiongroup_data['Date'] = pd.to_datetime(transactiongroup_data['Date'])

# Feature engineering: add month, year, and quarter columns
transactiongroup_data['Month'] = transactiongroup_data['Date'].dt.month
transactiongroup_data['Year'] = transactiongroup_data['Date'].dt.year
transactiongroup_data['Quarter'] = transactiongroup_data['Date'].dt.quarter

# Calculate total revenue per record as the sum of RateAmount and TollFeeAmount
transactiongroup_data['TotalRevenue'] = transactiongroup_data['RateAmount'] + transactiongroup_data['TollFeeAmount']

# Group by Year, Month, and Quarter to get monthly totals
monthly_data = transactiongroup_data.groupby(['Year', 'Month', 'Quarter'])['TotalRevenue'].sum().reset_index()

# Create a datetime index from Year and Month
monthly_data['Date'] = pd.to_datetime(monthly_data[['Year', 'Month']].assign(Day=1))
monthly_data.set_index('Date', inplace=True)

# Add lagged features for historical context
monthly_data['Lag1'] = monthly_data['TotalRevenue'].shift(1)
monthly_data['Lag2'] = monthly_data['TotalRevenue'].shift(2)
monthly_data['Lag3'] = monthly_data['TotalRevenue'].shift(3)

# Add rolling average feature to capture trends
monthly_data['RollingMean3'] = monthly_data['TotalRevenue'].rolling(window=3).mean().shift(1)

# Drop NaN values created by the lags and rolling mean
monthly_data.dropna(inplace=True)

# Prepare features (X) and target (y)
X = monthly_data[['Month', 'Year', 'Quarter', 'Lag1', 'Lag2', 'Lag3', 'RollingMean3']]
y = monthly_data['TotalRevenue']

# Split data into training and testing sets
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

# Define XGBoost model with initial hyperparameters
xgb_model = xgb.XGBRegressor(
    objective='reg:squarederror',
    n_estimators=300,
    learning_rate=0.05,
    max_depth=5,
    subsample=0.8,
    colsample_bytree=0.8,
    random_state=42
)

# Fit the model
xgb_model.fit(X_train, y_train, eval_set=[(X_test, y_test)], verbose=False)

# Predict on the test set and calculate R-squared, MAE, and MAPE
y_pred = xgb_model.predict(X_test)
r2 = r2_score(y_test, y_pred)
mae = mean_absolute_error(y_test, y_pred)
mape = mean_absolute_percentage_error(y_test, y_pred)
mean_actual = np.mean(y_test)
accuracy_percentage = (1 - mae / mean_actual) * 100

print(f"R-squared Score on Test Data: {r2:.2f}")
print(f"MAE: {mae:.2f}")
print(f"MAPE: {mape * 100:.2f}%")
print(f"Model Accuracy Percentage (based on MAE): {accuracy_percentage:.2f}%")

# Generate future dates for the next 6 months
future_dates = pd.date_range(start=monthly_data.index.max(), periods=6, freq='M')
future_df = pd.DataFrame({
    'Month': future_dates.month,
    'Year': future_dates.year,
    'Quarter': future_dates.quarter,
    'Lag1': [y_train.iloc[-1]] * len(future_dates),
    'Lag2': [y_train.iloc[-2]] * len(future_dates),
    'Lag3': [y_train.iloc[-3]] * len(future_dates),
    'RollingMean3': [monthly_data['RollingMean3'].iloc[-1]] * len(future_dates)
})

# Predict future values for the next 6 months
future_predictions = xgb_model.predict(future_df)

# Print the future predictions for review
future_df['PredictedRevenue'] = future_predictions
print(future_df)

# Save the model
joblib.dump(xgb_model, 'finance_model.joblib')
