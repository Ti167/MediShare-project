Part 1: How to Set Up XAMPP (Local Server)
XAMPP turns your computer into a local web server so it can run PHP and MySQL database applications.

1. Download and Install

Go to the official Apache Friends website and download XAMPP for Windows.

Run the installer file. (If Windows shows a warning about User Account Control/UAC, just click "OK").

Click "Next" through the setup screens, leaving all the default components checked. Let it install in the default C:\xampp folder.

2. Start the Server

Open the XAMPP Control Panel (you can search for it in your Windows Start menu).

Click the Start button next to Apache (this runs the website).

Click the Start button next to MySQL (this runs the database).

Both of their backgrounds should turn green, indicating the server is live.

3. Add the Project Files

Open your File Explorer and navigate to C:\xampp\htdocs.

make a folder named Medi-Share
and paste the entire project folder (e.g., Medi-Share) directly into this htdocs directory.

and then start the xampp apache and mysql and in the mysql click the admin and in that go to the  SQl tab and paste the all code of the medishare_schema.sql file.

Test it: Open your browser and type http://localhost/Medi-Share/. The application should now load on your screen.

