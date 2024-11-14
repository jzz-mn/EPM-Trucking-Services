import joblib
import pandas as pd
from sqlalchemy import create_engine

# Load the saved maintenance model
model = joblib.load('maintenance_model.joblib')

# Database connection URI (adjust based on your setup)
DATABASE_URI = 'mysql+pymysql://root:@localhost/epm_database'
engine = create_engine(DATABASE_URI)

def calculate_features(truck_id, year, month):
    """
    Calculates maintenance count, trip count, fuel usage, and monthly expenses for a given truck.
    """
    with engine.connect() as connection:
        # Maintenance count in the past year
        maintenance_count_query = f"""
            SELECT COUNT(*) FROM truckmaintenance
            WHERE TruckID = {truck_id} AND Year = {year}
        """
        maintenance_count = connection.execute(maintenance_count_query).scalar()
        
        # Trip count in the last year
        trip_count_query = f"""
            SELECT COUNT(*) FROM transactiongroup
            WHERE TruckID = {truck_id} AND YEAR(Date) = {year}
        """
        trip_count = connection.execute(trip_count_query).scalar()

        # Fuel usage for the month
        fuel_usage_query = f"""
            SELECT SUM(Liters) FROM fuel
            WHERE FuelID IN (SELECT FuelID FROM expenses WHERE ExpenseID IN 
                             (SELECT ExpenseID FROM transactiongroup WHERE TruckID = {truck_id}))
            AND MONTH(Date) = {month} AND YEAR(Date) = {year}
        """
        fuel_usage = connection.execute(fuel_usage_query).scalar() or 0

        # Monthly expenses
        monthly_expense_query = f"""
            SELECT SUM(TotalExpense) FROM expenses
            WHERE FuelID IN (SELECT FuelID FROM transactiongroup WHERE TruckID = {truck_id})
            AND MONTH(Date) = {month} AND YEAR(Date) = {year}
        """
        monthly_expense = connection.execute(monthly_expense_query).scalar() or 0

    return maintenance_count, trip_count, fuel_usage, monthly_expense

def predict_maintenance(truck_id, year, month, description_encoded):
    """
    Predict maintenance needs using calculated features.
    """
    maintenance_count, trip_count, fuel_usage, monthly_expense = calculate_features(truck_id, year, month)
    
    # Prepare the input data as per the model's requirements
    input_data = pd.DataFrame([[truck_id, year, month, description_encoded, maintenance_count, trip_count, fuel_usage, monthly_expense]], 
                              columns=['TruckID', 'Year', 'Month', 'DescriptionEncoded', 'MaintenanceCount', 'TripCount', 'FuelUsage', 'MonthlyExpense'])
    
    # Perform prediction
    prediction = model.predict(input_data)
    
    # Return the prediction (0 or 1)
    return int(prediction[0])
