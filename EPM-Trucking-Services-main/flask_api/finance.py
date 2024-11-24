import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import logging
from sqlalchemy import create_engine

logger = logging.getLogger(__name__)

class FinanceAnalyzer:
    def __init__(self):
        self.revenue_model = RandomForestRegressor(random_state=42)
        self.expenses_model = RandomForestRegressor(random_state=42)
        self.profit_model = RandomForestRegressor(random_state=42)
        self.metrics = {
            'revenue': {},
            'expenses': {},
            'profit': {}
        }
        self.last_features = None
        self.last_expense_features = None

    def _prepare_features(self, data):
        """Prepare features for the model"""
        # Convert date to datetime if it's not already
        data['Date'] = pd.to_datetime(data['Date'])
        
        # Create temporal features with adjusted column names
        features = pd.DataFrame({
            'TollFeeAmount': data['TollFeeAmount'],
            'RateAmount': data['RateAmount'],
            'TotalKGs': data['TotalKGs'],
            'FuelPrice': data['FuelPrice_Revenue'] if 'FuelPrice_Revenue' in data.columns else data['FuelPrice'],
            'Year': data['Date'].dt.year,
            'Month': data['Date'].dt.month,
            'Day': data['Date'].dt.day
        })
        
        return features

    def _prepare_expense_features(self, data):
        """Prepare features for expense model"""
        data['Date'] = pd.to_datetime(data['Date'])
        
        features = pd.DataFrame({
            'SalaryAmount': data['SalaryAmount'],
            'MobileAmount': data['MobileAmount'],
            'OtherAmount': data['OtherAmount'],
            'Liters': data['Liters'],
            'UnitPrice': data['UnitPrice'],
            'Year': data['Date'].dt.year,
            'Month': data['Date'].dt.month,
            'Day': data['Date'].dt.day
        })
        
        return features

    def train_revenue_model(self, data):
        """Train the revenue prediction model"""
        try:
            logger.info(f"Available columns for revenue model: {data.columns.tolist()}")
            features = self._prepare_features(data)
            target = data['Revenue'] if 'Revenue' in data.columns else data['Amount']
            
            # Store last features for future predictions
            self.last_features = features.iloc[-1]
            
            # Split the data
            X_train, X_test, y_train, y_test = train_test_split(
                features, target, test_size=0.2, random_state=42
            )
            
            # Train the model
            self.revenue_model.fit(X_train, y_train)
            
            # Calculate metrics
            y_pred = self.revenue_model.predict(X_test)
            self.metrics['revenue'] = {
                'mae': float(mean_absolute_error(y_test, y_pred)),
                'mse': float(mean_squared_error(y_test, y_pred)),
                'r2': float(r2_score(y_test, y_pred))
            }
            
            logger.info("Revenue model trained successfully")
            logger.info(f"Revenue model metrics: {self.metrics['revenue']}")
            return True
            
        except Exception as e:
            logger.error(f"Error training revenue model: {str(e)}")
            logger.exception("Full traceback:")
            return False

    def train_expenses_model(self, data):
        """Train the expenses prediction model"""
        try:
            features = self._prepare_expense_features(data)
            target = data['Expenses']
            
            # Store last features for future predictions
            self.last_expense_features = features.iloc[-1]
            
            # Split the data
            X_train, X_test, y_train, y_test = train_test_split(
                features, target, test_size=0.2, random_state=42
            )
            
            # Train the model
            self.expenses_model.fit(X_train, y_train)
            
            # Calculate metrics
            y_pred = self.expenses_model.predict(X_test)
            self.metrics['expenses'] = {
                'mae': float(mean_absolute_error(y_test, y_pred)),
                'mse': float(mean_squared_error(y_test, y_pred)),
                'r2': float(r2_score(y_test, y_pred))
            }
            
            logger.info("Expenses model trained successfully")
            logger.info(f"Expenses model metrics: {self.metrics['expenses']}")
            return True
            
        except Exception as e:
            logger.error(f"Error training expenses model: {str(e)}")
            logger.exception("Full traceback:")
            return False

    def train_profit_model(self, historical_data):
        """Train the profit prediction model"""
        try:
            logger.info("Starting profit model training...")
            logger.info(f"Available columns: {historical_data.columns.tolist()}")
            
            # Revenue is directly from transactiongroup.Amount
            historical_data['Revenue'] = historical_data['Amount']  # Changed from RateAmount * TotalKGs
            
            # Total Expenses is from expenses.TotalExpense + fuel.Amount
            historical_data['TotalExpenses'] = (
                historical_data['TotalExpense'] +  # From expenses table
                historical_data['Amount']  # From fuel table
            )
            
            # Calculate Profit
            historical_data['Profit'] = historical_data['Revenue'] - historical_data['TotalExpenses']
            
            # Prepare features for profit model
            features = pd.DataFrame({
                'Revenue': historical_data['Revenue'],
                'TotalExpenses': historical_data['TotalExpenses'],
                'Year': pd.to_datetime(historical_data['Date']).dt.year,
                'Month': pd.to_datetime(historical_data['Date']).dt.month,
                'Day': pd.to_datetime(historical_data['Date']).dt.day
            })
            
            target = historical_data['Profit']
            
            # Split the data
            X_train, X_test, y_train, y_test = train_test_split(
                features, target, test_size=0.2, random_state=42
            )
            
            # Train the model
            self.profit_model.fit(X_train, y_train)
            
            # Calculate metrics
            y_pred = self.profit_model.predict(X_test)
            self.metrics['profit'] = {
                'mae': float(mean_absolute_error(y_test, y_pred)),
                'mse': float(mean_squared_error(y_test, y_pred)),
                'r2': float(r2_score(y_test, y_pred))
            }
            
            logger.info("Profit model trained successfully")
            return True
            
        except Exception as e:
            logger.error(f"Error training profit model: {str(e)}")
            logger.exception("Full traceback:")
            return False

    def predict_profit(self, last_date, periods=90):
        """Predict profit for future dates"""
        try:
            # Get revenue and expense predictions
            revenue_forecast = self.predict_revenue(last_date, periods)
            expense_forecast = self.predict_expenses(last_date, periods)
            
            if revenue_forecast is None or expense_forecast is None:
                raise Exception("Failed to get revenue or expense forecasts")
            
            # Calculate profit directly from revenue and expenses
            forecast = pd.DataFrame({
                'Date': revenue_forecast['Date'],
                'Revenue': revenue_forecast['Revenue'],
                'Expenses': expense_forecast['Expenses'],
                'Profit': (revenue_forecast['Revenue'] - expense_forecast['Expenses']).round(2)
            })
            
            return forecast
            
        except Exception as e:
            logger.error(f"Error predicting profit: {str(e)}")
            logger.exception("Full traceback:")
            return None

    def analyze_finances(self, historical_data, forecast_days=90):
        """Complete financial analysis including revenue predictions"""
        try:
            # Store last known features for future predictions
            self.last_features = self._prepare_features(historical_data)
            
            # Train revenue model
            self.train_revenue_model(historical_data)
            
            # Make predictions
            last_date = historical_data['Date'].max()
            revenue_forecast = self.predict_revenue(last_date, forecast_days)
            
            if revenue_forecast is None:
                raise Exception("Failed to generate revenue forecast")

            return {
                "forecast": revenue_forecast.to_dict(orient='records'),
                "metrics": self.metrics,
                "forecast_period": {
                    "start": revenue_forecast['Date'].min().strftime('%Y-%m-%d'),
                    "end": revenue_forecast['Date'].max().strftime('%Y-%m-%d')
                }
            }

        except Exception as e:
            logger.error(f"Error in financial analysis: {str(e)}")
            raise

    def predict_revenue(self, last_date, periods=90):
        """Predict revenue for future dates"""
        try:
            # Generate future dates
            future_dates = pd.date_range(
                start=last_date + pd.Timedelta(days=1),
                periods=periods,
                freq='D'
            )

            # Create future features using the correct column names
            future_features = pd.DataFrame({
                'TollFeeAmount': [self.last_features['TollFeeAmount']] * periods,
                'RateAmount': [self.last_features['RateAmount']] * periods,
                'TotalKGs': [self.last_features['TotalKGs']] * periods,
                'FuelPrice': [self.last_features['FuelPrice']] * periods,
                'Year': future_dates.year,
                'Month': future_dates.month,
                'Day': future_dates.day
            })

            # Make predictions
            predictions = self.revenue_model.predict(future_features)

            # Create forecast DataFrame
            forecast = pd.DataFrame({
                'Date': future_dates,
                'Revenue': predictions.round(2)
            })

            return forecast

        except Exception as e:
            logger.error(f"Error predicting revenue: {str(e)}")
            logger.exception("Full traceback:")
            return None

    def predict_expenses(self, last_date, periods=90):
        """Predict expenses for future dates"""
        try:
            # Generate future dates
            future_dates = pd.date_range(
                start=last_date + pd.Timedelta(days=1),
                periods=periods,
                freq='D'
            )
            
            # Create future features using the last known values
            future_features = pd.DataFrame({
                'SalaryAmount': [self.last_expense_features['SalaryAmount']] * periods,
                'MobileAmount': [self.last_expense_features['MobileAmount']] * periods,
                'OtherAmount': [self.last_expense_features['OtherAmount']] * periods,
                'Liters': [self.last_expense_features['Liters']] * periods,
                'UnitPrice': [self.last_expense_features['UnitPrice']] * periods,
                'Year': future_dates.year,
                'Month': future_dates.month,
                'Day': future_dates.day
            })

            # Make predictions
            predictions = self.expenses_model.predict(future_features)

            # Create forecast DataFrame
            forecast = pd.DataFrame({
                'Date': future_dates,
                'Expenses': predictions.round(2)
            })

            return forecast

        except Exception as e:
            logger.error(f"Error predicting expenses: {str(e)}")
            logger.exception("Full traceback:")
            return None

# Test section - only runs if file is executed directly
if __name__ == "__main__":
    import mysql.connector
    
    # Setup basic logging
    logging.basicConfig(level=logging.INFO)
    
    # Database configuration
    DB_CONFIG = {
        'host': 'localhost',
        'user': 'root',
        'password': '',
        'database': 'epm_database'
    }

    print("Starting analysis...")

    try:
        print("Connecting to database...")
        # Create SQLAlchemy engine
        engine = create_engine(f"mysql+pymysql://{DB_CONFIG['user']}:{DB_CONFIG['password']}@{DB_CONFIG['host']}/{DB_CONFIG['database']}")
        
        # Revenue query
        revenue_query = """
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
        
        # Expenses query
        expenses_query = """
            SELECT 
                e.Date,
                e.SalaryAmount,
                e.MobileAmount,
                e.OtherAmount,
                (e.SalaryAmount + e.MobileAmount + e.OtherAmount) as TotalExpense,
                f.Liters,
                f.UnitPrice,
                f.Amount as FuelAmount,
                ((e.SalaryAmount + e.MobileAmount + e.OtherAmount) + f.Amount) as Expenses
            FROM expenses e
            LEFT JOIN fuel f ON e.Date = f.Date
            ORDER BY e.Date
        """
        
        print("Fetching revenue data...")
        df_transac = pd.read_sql(revenue_query, engine)
        print("Fetching expenses data...")
        df_expenses = pd.read_sql(expenses_query, engine)
        
        print(f"\nRevenue data shape: {df_transac.shape}")
        print("First few rows of revenue data:")
        print(df_transac.head())
        
        print(f"\nExpenses data shape: {df_expenses.shape}")
        print("First few rows of expenses data:")
        print(df_expenses.head())
        
        if df_transac.empty or df_expenses.empty:
            raise Exception("No data retrieved from database")
        
        # Initialize analyzer
        print("\nInitializing analyzer...")
        analyzer = FinanceAnalyzer()
        
        # Revenue Analysis
        print("\nPerforming revenue analysis...")
        revenue_analysis = analyzer.analyze_finances(df_transac)
        
        # Print revenue metrics
        revenue_metrics = revenue_analysis['metrics']['revenue']
        print("\nRevenue Model Evaluation Metrics:")
        print(f"MAE (Mean Absolute Error): {revenue_metrics['mae']:.2f}")
        print(f"MSE (Mean Squared Error): {revenue_metrics['mse']:.2f}")
        print(f"R² (R-squared): {revenue_metrics['r2']:.2f}")
        
        print("\nFirst few rows of revenue forecast:")
        revenue_forecast_df = pd.DataFrame(revenue_analysis['forecast'])
        print(revenue_forecast_df.head())
        
        # Expenses Analysis
        print("\nPerforming expenses analysis...")
        expenses_analysis = analyzer.analyze_expenses(df_expenses)
        
        # Print expenses metrics
        expenses_metrics = expenses_analysis['metrics']['expenses']
        print("\nExpenses Model Evaluation Metrics:")
        print(f"MAE (Mean Absolute Error): {expenses_metrics['mae']:.2f}")
        print(f"MSE (Mean Squared Error): {expenses_metrics['mse']:.2f}")
        print(f"R² (R-squared): {expenses_metrics['r2']:.2f}")
        
        print("\nFirst few rows of expenses forecast:")
        expenses_forecast_df = pd.DataFrame(expenses_analysis['forecast'])
        print(expenses_forecast_df.head())
        
    except mysql.connector.Error as e:
        print(f"Database Error: {e}")
        print(f"Error Code: {e.errno}")
        print(f"SQLSTATE: {e.sqlstate}")
        print(f"Error Message: {e.msg}")
    except Exception as e:
        print(f"Error during testing: {str(e)}")
        import traceback
        print("\nFull error traceback:")
        traceback.print_exc()
