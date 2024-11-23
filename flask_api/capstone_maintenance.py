import joblib
import pandas as pd
from sqlalchemy import create_engine
from sqlalchemy.sql import text


# Load the saved maintenance model
model = joblib.load('maintenance_model.joblib')  # Updated model filename

DATABASE_URI = 'mysql+pymysql://root:@localhost/epm_database'
engine = create_engine(DATABASE_URI)

def calculate_features(truck_id, year, month):
    """
    Calculates features required for predicting maintenance.
    """
    with engine.connect() as connection:
        # Fetch the last maintenance year
        last_maintenance_query = text("""
            SELECT MAX(Year) AS LastMaintenanceYear
            FROM truckmaintenance
            WHERE TruckID = :truck_id
        """)
        last_maintenance_year = connection.execute(last_maintenance_query, {"truck_id": truck_id}).scalar()
        if last_maintenance_year is None:
            last_maintenance_year = year  # Default to the current year if no data

        time_since_last_maintenance = year - last_maintenance_year

        # Fetch average load
        avg_load_query = text("""
            SELECT AVG(TotalKGs) AS AvgLoad
            FROM transactiongroup
            WHERE TruckID = :truck_id AND YEAR(Date) = :year
        """)
        average_load = connection.execute(avg_load_query, {"truck_id": truck_id, "year": year}).scalar()

        # Handle invalid or missing data
        if average_load is None:
            average_load = 0  # Default to 0 if no data is available
        else:
            average_load = float(average_load)  # Ensure it's a float

    return time_since_last_maintenance, average_load


def predict_maintenance(truck_id, year, month, connection):
    """
    Predict maintenance needs using calculated features.
    """
    def calculate_features(truck_id, year, month, connection):
        """
        Calculates features required for predicting maintenance.
        """
        # Fetch the last maintenance year
        last_maintenance_query = text("""
            SELECT MAX(Year) AS LastMaintenanceYear
            FROM truckmaintenance
            WHERE TruckID = :truck_id
        """)
        last_maintenance_year = connection.execute(last_maintenance_query, {"truck_id": truck_id}).scalar()
        if last_maintenance_year is None:
            last_maintenance_year = year  # Default to the current year if no data

        time_since_last_maintenance = year - last_maintenance_year

        # Fetch average load
        avg_load_query = text("""
            SELECT AVG(TotalKGs) AS AvgLoad
            FROM transactiongroup
            WHERE TruckID = :truck_id AND YEAR(Date) = :year
        """)
        average_load = connection.execute(avg_load_query, {"truck_id": truck_id, "year": year}).scalar()

        # Handle invalid or missing data
        if average_load is None:
            average_load = 0  # Default to 0 if no data is available
        else:
            average_load = float(average_load)  # Ensure it's a float

        return time_since_last_maintenance, average_load

    # Calculate required features
    time_since_last_maintenance, average_load = calculate_features(truck_id, year, month, connection)

    # Prepare input data for the model
    input_data = pd.DataFrame([[time_since_last_maintenance, average_load]], 
                              columns=['TimeSinceLastMaintenance', 'AverageLoad'])

    # Ensure all columns are numeric
    input_data = input_data.astype(float)

    # Perform prediction
    prediction = model.predict(input_data)

    return {
        "TruckID": truck_id,
        "Year": year,
        "Month": month,
        "MaintenanceStatus": "Yes" if prediction[0] == 1 else "No",
        "Reason": "High average load and/or long gap since last maintenance." if prediction[0] == 1 else
                  "Truck usage and maintenance gap are within acceptable limits."
    }


