# My web projects

The code for the two web projects in my portfolio. Both are **PHP + MySQL** and run in XAMPP.

| Project | What it is | Live site |
|---|---|---|
| [`likhub/`](likhub) | LikHub, a site for student artists: lessons, gallery and commissions. I co-founded it and did the development. | https://likhub.online/likhub/ |
| [`kabakal/`](kabakal) | Kabakal Fitness Equipment, an online shop with an admin side for products, inventory, orders and GCash payments. | none (run it locally) |

These are cleaned copies for sharing: no real passwords, API keys or customer data. Emails are not sent in the LikHub copy; codes show on screen instead.

## How to run one on your computer

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Copy the project folder into `C:\xampp\htdocs\`. The folder name must stay the same:
   - `likhub` → `C:\xampp\htdocs\likhub`
   - `kabakal` → `C:\xampp\htdocs\kabakal`
3. Open http://localhost/phpmyadmin, go to **Import**, and pick the matching file from [`sql/`](sql):
   - `likhub.sql` (creates `likhub_db`)
   - `kabakal.sql` (creates `kabakal_demo`)
4. Open http://localhost/likhub/ or http://localhost/kabakal/

## Demo logins

| Site | Email / username | Password |
|---|---|---|
| LikHub | `client@likhub.local` (client), `drex@likhub.local` (artist), `lemuel@likhub.local` (admin) | `likhub-demo` |
| Kabakal shop | `customer@kabakal.local` | `kabakal-demo` |
| Kabakal staff (`/kabakal/admin/`) | `admin` | `kabakal-demo` |
