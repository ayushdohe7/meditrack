# MediTrack — Doctor/Patient Appointment Dashboard

A full dark-themed clinic management web app.
Frontend: HTML5, CSS3, Bootstrap 5, JavaScript, Chart.js.
Backend: PHP + MySQL (mysqli, prepared statements).

## What's included
- Landing page (choose Doctor / Patient)
- Doctor login & registration
- Patient login & registration
- Role-aware Dashboard (stat cards, donut chart, today's appointments,
  next patient details, appointment requests, mini calendar)
- Appointments list (filter by status, cancel / start / complete)
- Appointment Page (patient books, doctor schedules manually)
- Appointment Requests (doctor approves / rejects patient requests)
- Patient Details (doctor: manage every patient's medical record in a
  modal; patient: edit their own health record) — all saved to MySQL
- Payment module (doctor records payments, sees monthly income;
  patient views/pays dues)
- Profile page with photo upload
- Settings (preferences, consultation fee, change password)
- Logout
- Built-in AI chatbot widget (bottom-right, on every page) — answers
  questions about appointments, payments, pending requests etc. by
  querying your live database. No external API key required to work;
  a stub is included in `api/chatbot.php` if you want to wire up a
  real AI provider later.

## How to install (XAMPP)
1. Start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Unzip this project so the folder is placed at:
   `C:\xampp\htdocs\meditrack` (Windows) or `/Applications/XAMPP/htdocs/meditrack` (Mac) or `/opt/lampp/htdocs/meditrack` (Linux).
   > The final path must be `htdocs/meditrack` because the app's menu
   > links use `/meditrack/...`. If you rename the folder, update the
   > links in `includes/sidebar.php`, `includes/chatbot.php`'s JS fetch
   > URL, and every `/meditrack/` reference accordingly.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Click **Import**, choose `sql/meditrack.sql`, and click **Go**.
   This creates the `meditrack` database with all tables.
5. Check `config/db.php` — defaults are `root` / no password (standard
   XAMPP setup). Edit if your MySQL user/password differ.
6. Visit `http://localhost/meditrack/` in your browser.
7. Click **Register as Doctor** and/or **Register as Patient** to
   create your first accounts (there are no pre-seeded demo logins —
   registering guarantees a real, working password hash).
8. Log in and explore the dashboard!

## Notes
- All passwords are hashed with PHP's `password_hash()` and verified
  with `password_verify()` — never stored in plain text.
- All SQL queries that take user input use prepared statements to
  prevent SQL injection.
- Profile photos are uploaded to `uploads/doctors/` or
  `uploads/patients/`.
- The dashboard, appointments, patient details, and payment pages are
  fully dynamic — every number and list is pulled live from MySQL.
- Dark theme is the default and only theme shipped; colors live in
  `assets/css/style.css` under the `:root` CSS variables if you'd like
  to customize the palette.
