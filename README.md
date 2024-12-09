# **Banquet Easy Data Extractor for Tally Prime Integration**

## **Project Description**

This project is designed to extract booking data from the **Banquet Easy** website and generate an XML file that can be imported directly into **Tally Prime**. The PHP script will allow you to pull booking information such as **sales**, **receipts**, and **ledgers** within a specified date range and create a well-structured XML file that can be imported into Tally Prime without requiring manual data entry.

The goal of this project is to automate the process of transferring booking data into Tally Prime, improving efficiency and reducing human error.

### **Problem Statement**

- **Extract booking data** (sales, receipts, and ledgers) from Banquet Easy within a given date range.
- **Generate an XML file** that is directly compatible with Tally Prime for easy import.
- **Eliminate manual data entry** into Tally Prime by automating the data extraction and import process.

## **Features**

- ***Extract booking data***: Filter bookings by a specific date range (from a start date to an end date).
- **XML File Generation**: The script will output an XML file formatted specifically for Tally Prime import.
- **Sales, Receipts, and Ledgers Data**: Automatically fetch and format sales, receipts, and ledger data.
- **Date Range Filter**: Users can specify the date range for booking data extraction.
- **Easy Integration with Tally Prime**: The generated XML file can be easily imported into Tally Prime, updating the ledgers and transaction data.

## **Technologies Used**

- **PHP**: For backend script to process data extraction and generate XML.
- **PostgreSQL**: To query the Banquet Easy database for booking data.
- **Tally Prime XML Format**: For structuring the data into XML compatible with Tally Prime.
- **XAMPP**: Local development environment for running the PHP code.
- **HTML/CSS**: For frontend UI where the user can specify the date range and download the XML file.

## **Setup Instructions**

### **Prerequisites**

Before you begin, ensure that you have the following installed:

- **XAMPP** (or any local server for running PHP).
- **PostgreSQL** running with access to the Banquet Easy database.
- **PHP 7.4+** installed.
- **Composer** (if using PHP libraries for dependencies).
- **Tally Prime** installed for XML file import.

### **1. Clone the Repository**

Start by cloning this repository to your local machine:

```bash
git clone https://github.com/yourusername/banquet-easy-tally-integration.git
cd banquet-easy-tally-integration
```

### **2. Set Up Database Connection**

1. Open the `config.php` file and update the database connection details:

    ```php
    <?php
    define('DB_HOST', 'localhost');      // Database Host
    define('DB_PORT', '5432');           // Database Port
    define('DB_NAME', 'your_database_name');  // Database Name
    define('DB_USER', 'your_database_user');  // Database Username
    define('DB_PASS', 'your_database_password');  // Database Password
    ```

2. Ensure that PostgreSQL is running and that you have access to the **Banquet Easy** database.

### **3. Install Dependencies (Optional)**

If you're using Composer for additional PHP libraries or dependencies, run the following command:

```bash
composer install
```

### **4. Start XAMPP**

Make sure the XAMPP services (Apache and PostgreSQL) are running to execute the PHP code and connect to the database.

### **5. Access the Script**

Once everything is set up, access the script by navigating to:

```bash
http://localhost/banquet-easy-tally-integration/index.php
```

### **6. Usage Instructions**

#### **Step-by-Step Guide**

1. **Enter Date Range**:  
   - Navigate to the script in your browser (`http://localhost/banquet-easy-tally-integration/index.php`) and enter the start and end dates for the bookings you want to extract.
   - The format for dates should be **YYYY-MM-DD** (e.g., `2024-01-01` to `2024-01-31`).

2. **Run the Extraction**:  
   Once the date range is entered, click on the **"Generate XML"** button to initiate the extraction process.

3. **Download the XML File**:  
   The script will generate an XML file containing all the booking data (sales, receipts, and ledgers) within the specified date range. You will be given a download link to save this XML file to your system.

4. **Import into Tally Prime**:  
   Open **Tally Prime** and navigate to the **Import** section. Import the generated XML file to automatically update the ledgers, sales, and receipts in Tally Prime.

#### **Sample URL for Date Range Extraction**

You can also manually input the date range by specifying the start and end dates in the URL:

```bash
http://localhost/banquet-easy-tally-integration/index.php?start_date=2024-01-01&end_date=2024-01-31
```

### **7. How the Script Works**

1. **User Input**: The user provides the **start** and **end date** to filter bookings.
2. **Database Connection**: The script connects to the PostgreSQL database using the provided credentials in `config.php`.
3. **Data Extraction**: The script queries the database for booking data within the provided date range.
4. **XML Generation**: The script processes the extracted data and generates an XML file in Tally Prime’s format, including relevant details such as sales, receipts, and ledgers.
5. **Download**: The user can then download the XML file and import it into Tally Prime.

### **8. Tally Prime XML Format**

Ensure that the generated XML follows the required format for Tally Prime import. A sample XML structure might look like this:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<TallyMessage xmlns:UDF="TallyUDF">
    <Header>
        <TallyRequest>Import</TallyRequest>
        <TallyData>
            <Voucher>
                <VoucherTypeName>Sales</VoucherTypeName>
                <VoucherDate>2024-01-15</VoucherDate>
                <LedgerName>Cash</LedgerName>
                <Amount>1000</Amount>
            </Voucher>
            <Voucher>
                <VoucherTypeName>Receipt</VoucherTypeName>
                <VoucherDate>2024-01-20</VoucherDate>
                <LedgerName>Bank</LedgerName>
                <Amount>500</Amount>
            </Voucher>
        </TallyData>
    </Header>
</TallyMessage>
```

### **9. Error Handling**

- **Database Connection Errors**: If the connection to PostgreSQL fails, the script will show an error message.
- **Empty Date Range**: If no data is found for the selected date range, an appropriate message will be displayed.
- **Invalid Input**: If the date format is incorrect, the script will prompt the user to provide a valid date format.

## **Contribution**

We welcome contributions! If you'd like to contribute to this project, feel free to fork the repository and submit a pull request. Before contributing, please make sure that:

- You follow the established **coding standards**.
- You include **clear and descriptive commit messages**.
- Ensure that any new features are well-documented.

### **How to Contribute**

1. Fork the repository.
2. Create a new branch for your changes.
3. Commit your changes with a descriptive message.
4. Push your changes and open a pull request.

## **License**

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

## **Contact**

For any questions, suggestions, or support:

- **Yash Chavan**  
- **yashchavan4628@gmail.com**  
- **GitHub Profile**: [Your GitHub Profile](https://github.com/YashChavanWeb)

