from flask import jsonify
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import (
    accuracy_score, 
    precision_score, 
    recall_score, 
    f1_score,
    confusion_matrix
)
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split
import numpy as np
import pandas as pd
from datetime import datetime, timedelta
import mysql.connector
import logging

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'epm_database'
}

class MaintenancePredictor:
    def __init__(self):
        self.model = None
        self.scaler = StandardScaler()
        self.features = [
            'MaintenanceCount',   
            'MaintenanceCost'     # Simplified features
        ]
        self.feature_importance = {}
        self.metrics = {}

    def get_historical_data(self):
        try:
            conn = mysql.connector.connect(**DB_CONFIG)
            logger.info("Database connection established")
            
            query = """
                WITH MonthlyMaintenance AS (
                    SELECT 
                        tm.TruckID,
                        ti.PlateNo,
                        ti.TruckBrand,
                        tm.Year,
                        tm.Month,
                        COUNT(*) as MaintenanceCount,
                        SUM(CASE WHEN tm.Amount > 0 THEN tm.Amount ELSE 0 END) as MaintenanceCost
                    FROM truckmaintenance tm
                    JOIN trucksinfo ti ON tm.TruckID = ti.TruckID
                    WHERE tm.Year >= YEAR(DATE_SUB(NOW(), INTERVAL 2 YEAR))
                    GROUP BY 
                        tm.TruckID, 
                        ti.PlateNo,
                        ti.TruckBrand,
                        tm.Year,
                        tm.Month
                    ORDER BY tm.Year DESC, 
                        CASE tm.Month 
                            WHEN 'JANUARY' THEN 1
                            WHEN 'FEBRUARY' THEN 2
                            WHEN 'MARCH' THEN 3
                            WHEN 'APRIL' THEN 4
                            WHEN 'MAY' THEN 5
                            WHEN 'JUNE' THEN 6
                            WHEN 'JULY' THEN 7
                            WHEN 'AUGUST' THEN 8
                            WHEN 'SEPTEMBER' THEN 9
                            WHEN 'OCTOBER' THEN 10
                            WHEN 'NOVEMBER' THEN 11
                            WHEN 'DECEMBER' THEN 12
                        END DESC
                )
                SELECT *,
                    CASE 
                        WHEN MaintenanceCost > 0 OR MaintenanceCount >= 2 THEN 1
                        ELSE 0 
                    END as NeedsMaintenance
                FROM MonthlyMaintenance
            """
            
            df = pd.read_sql(query, conn)
            conn.close()
            
            if len(df) < 1:
                raise Exception("No maintenance records found")
            
            logger.info(f"Retrieved {len(df)} records")
            logger.info(f"Maintenance events: {df['NeedsMaintenance'].sum()}")
            
            return df
            
        except Exception as e:
            logger.error(f"Database error: {str(e)}")
            raise

    def train_model(self):
        try:
            df = self.get_historical_data()
            logger.info(f"Training data size: {len(df)} records")
            
            if len(df) < 5:
                raise Exception("Not enough maintenance history (minimum 5 records needed)")
            
            X = df[self.features]
            y = df['NeedsMaintenance']
            
            # Split data for validation
            X_train, X_test, y_train, y_test = train_test_split(
                X, y, test_size=0.2, random_state=42, stratify=y
            )
            
            # Scale features
            X_train_scaled = self.scaler.fit_transform(X_train)
            X_test_scaled = self.scaler.transform(X_test)
            
            # Train model
            self.model = LogisticRegression(
                class_weight='balanced',
                random_state=42,
                max_iter=1000
            )
            
            self.model.fit(X_train_scaled, y_train)
            
            # Calculate and store metrics
            y_pred = self.model.predict(X_test_scaled)
            self.metrics = {
                'accuracy': round(accuracy_score(y_test, y_pred) * 100, 2),
                'precision': round(precision_score(y_test, y_pred) * 100, 2),
                'recall': round(recall_score(y_test, y_pred) * 100, 2)
            }
            
            # Store feature importance
            self.feature_importance = dict(zip(
                self.features,
                [abs(coef) * 100 / sum(abs(self.model.coef_[0])) for coef in self.model.coef_[0]]
            ))
            
            logger.info("Model trained successfully")
            return True
            
        except Exception as e:
            logger.error(f"Training error: {str(e)}")
            return False

    def predict_maintenance(self):
        try:
            if self.model is None:
                raise Exception("Model not trained yet")
            
            recent_data = self.get_historical_data()
            future_predictions = []
            
            # Get the latest year and month from the database
            conn = mysql.connector.connect(**DB_CONFIG)
            cursor = conn.cursor()
            
            query = """
                SELECT Year, Month 
                FROM truckmaintenance 
                ORDER BY Year DESC, 
                    CASE Month 
                        WHEN 'JANUARY' THEN 1
                        WHEN 'FEBRUARY' THEN 2
                        WHEN 'MARCH' THEN 3
                        WHEN 'APRIL' THEN 4
                        WHEN 'MAY' THEN 5
                        WHEN 'JUNE' THEN 6
                        WHEN 'JULY' THEN 7
                        WHEN 'AUGUST' THEN 8
                        WHEN 'SEPTEMBER' THEN 9
                        WHEN 'OCTOBER' THEN 10
                        WHEN 'NOVEMBER' THEN 11
                        WHEN 'DECEMBER' THEN 12
                    END DESC
                LIMIT 1
            """
            
            cursor.execute(query)
            latest_year, latest_month = cursor.fetchone()
            cursor.close()
            conn.close()
            
            # Convert month names to numbers for calculations
            month_to_num = {
                'JANUARY': 1, 'FEBRUARY': 2, 'MARCH': 3, 'APRIL': 4,
                'MAY': 5, 'JUNE': 6, 'JULY': 7, 'AUGUST': 8,
                'SEPTEMBER': 9, 'OCTOBER': 10, 'NOVEMBER': 11, 'DECEMBER': 12
            }
            num_to_month = {v: k for k, v in month_to_num.items()}
            
            # Get the next month after the latest maintenance
            start_month_num = month_to_num[latest_month] + 1
            start_year = latest_year
            
            # Handle year rollover
            if start_month_num > 12:
                start_month_num = 1
                start_year += 1
            
            logger.info(f"Starting predictions from {num_to_month[start_month_num]} {start_year}")
            
            # Get unique trucks
            unique_trucks = recent_data.drop_duplicates(
                subset=['TruckID', 'PlateNo']
            )[['TruckID', 'PlateNo', 'TruckBrand']].astype({
                'TruckID': int,
                'PlateNo': str,
                'TruckBrand': str
            })
            
            for _, truck in unique_trucks.iterrows():
                truck_history = recent_data[
                    recent_data['TruckID'] == truck['TruckID']
                ].sort_values(['Year', 'Month'])
                
                if len(truck_history) < 1:
                    continue
                    
                # Use last 3 months of data
                latest_records = truck_history.head(3)
                
                # Calculate features with some randomization for variety
                maintenance_count = float(latest_records['MaintenanceCount'].mean())
                maintenance_cost = float(latest_records['MaintenanceCost'].mean())
                
                features = np.array([
                    maintenance_count * (1 + np.random.uniform(-0.1, 0.1)),
                    maintenance_cost * (1 + np.random.uniform(-0.1, 0.1))
                ]).reshape(1, -1)
                
                features_scaled = self.scaler.transform(features)
                base_prob = float(self.model.predict_proba(features_scaled)[0][1])
                
                # Generate predictions for next 6 months
                for i in range(6):
                    future_month_num = start_month_num + i
                    future_year = start_year
                    
                    if future_month_num > 12:
                        future_month_num = future_month_num - 12
                        future_year += 1
                    
                    future_month = num_to_month[future_month_num]
                    month_factor = 1 - (i * 0.1)
                    adjusted_prob = base_prob * month_factor
                    final_prob = float(min(1.0, max(0.0, 
                        adjusted_prob * (1 + np.random.uniform(-0.1, 0.1))
                    )))
                    
                    future_predictions.append({
                        'truck_id': int(truck['TruckID']),
                        'plate_no': str(truck['PlateNo']),
                        'brand': str(truck['TruckBrand']),
                        'year': int(future_year),
                        'month': str(future_month),
                        'month_display': f"{future_month} {future_year}",
                        'needs_maintenance': bool(final_prob > 0.5),
                        'probability': float(final_prob)
                    })
            
            logger.info(f"Generated {len(future_predictions)} predictions")
            return future_predictions
            
        except Exception as e:
            logger.error(f"Prediction error: {str(e)}")
            return None

    def evaluate_model(self):
        try:
            if self.model is None:
                raise Exception("Model not trained yet")
            
            df = self.get_historical_data()
            X = df[self.features]
            y = df['NeedsMaintenance']
            
            # Scale features
            X_scaled = self.scaler.transform(X)
            
            # Get predictions
            y_pred = self.model.predict(X_scaled)
            y_prob = self.model.predict_proba(X_scaled)[:, 1]
            
            # Calculate metrics
            conf_matrix = confusion_matrix(y, y_pred)
            tn, fp, fn, tp = conf_matrix.ravel()
            
            # Calculate historical maintenance costs and potential savings
            recent_data = self.get_historical_data()
            last_month_cost = recent_data.loc[
                recent_data['Year'] == recent_data['Year'].max()
            ]['MaintenanceCost'].sum()
            
            # Calculate potential savings based on false positives reduction
            # If precision is 100%, use a more conservative estimate
            if self.metrics['precision'] == 100:
                savings_rate = 0.15  # 15% potential savings
            else:
                savings_rate = (100 - self.metrics['precision']) / 100
            
            potential_savings = last_month_cost * savings_rate
            
            evaluation = {
                'accuracy': round(accuracy_score(y, y_pred) * 100, 2),
                'precision': round(precision_score(y, y_pred) * 100, 2),
                'recall': round(recall_score(y, y_pred) * 100, 2),
                'f1_score': round(f1_score(y, y_pred) * 100, 2),
                'confusion_matrix': {
                    'true_negative': int(tn),
                    'false_positive': int(fp),
                    'false_negative': int(fn),
                    'true_positive': int(tp)
                },
                'model_details': {
                    'model_type': 'LogisticRegression',
                    'feature_importance': {
                        feature: round(importance, 2)
                        for feature, importance in self.feature_importance.items()
                    }
                },
                'data_stats': {
                    'total_samples': len(df),
                    'maintenance_events': int(y.sum()),
                    'maintenance_rate': round(y.mean() * 100, 2),
                    'historical_cost': float(last_month_cost),
                    'potential_savings': float(potential_savings)
                }
            }
            
            return evaluation
            
        except Exception as e:
            logger.error(f"Evaluation error: {str(e)}")
            return None

