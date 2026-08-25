# MAHA Fastfood

سایت منوی رستوران **ماها** — فرانت HTML/CSS/JS و بک‌اند PHP برای هاست‌های معمولی (cPanel).

مخزن: [github.com/saman-alayee/MAHA](https://github.com/saman-alayee/MAHA)

Node.js لازم نیست.

## امکانات

- صفحه اصلی، منو، جزئیات غذا و تماس با همان ظاهر فعلی
- پنل مدیریت غذا، دسته، تصویر، قیمت و متن‌های سایت
- ورود مدیر با نشست PHP
- آپلود تصویر غذا
- نمایش/مخفی کردن و مرتب‌سازی غذاها
- تنظیمات هاست با فایل `.env`

## گرفتن پروژه از گیت‌هاب

```bash
git clone https://github.com/saman-alayee/MAHA.git
cd MAHA
```

فایل `.env` داخل مخزن نیست (عمدی). از روی نمونه بسازید:

```bash
copy .env.example .env
```

روی لینوکس/مک:

```bash
cp .env.example .env
```

همین کار را برای `public/.env` هم انجام دهید، چون روی هاست همین فایل خوانده می‌شود.

## ساختار

```text
public/                 همین پوشه را روی public_html آپلود کنید
  index.html            سایت
  admin.html            پنل مدیریت
  api/index.php         REST API
  app/                  کد PHP
  data/                 دیتابیس SQLite (اگر MySQL نباشد)
  uploads/foods/        تصاویر آپلودشده
  .env                  تنظیمات این هاست (از .env.example ساخته شود)
```

## نصب روی هاست cPanel

1. محتویات پوشه `public` را داخل `public_html` آپلود کنید.
2. فایل `.env` را از روی `.env.example` بسازید و پر کنید.
3. پوشه‌های `data` و `uploads/foods` باید قابل نوشتن باشند (دسترسی `755` یا `775`).
4. سایت را باز کنید. جدول‌ها و منوی اولیه در اولین ورود ساخته می‌شوند.

### روش پیشنهادی: MySQL

در cPanel یک دیتابیس و کاربر بسازید و به `.env` بدهید:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_NAME=نام_دیتابیس
DB_USER=نام_کاربر
DB_PASS=رمز_دیتابیس
ADMIN_USERNAME=admin
ADMIN_PASSWORD=8242026
COOKIE_SECURE=false
```

اگر دامنه SSL دارد، `COOKIE_SECURE=true` بگذارید.

### روش ساده‌تر: SQLite

اگر افزونه PDO SQLite روی هاست فعال است:

```env
DB_CONNECTION=sqlite
DB_PATH=data/maha.db
```

برای منوی رستوران با یک مدیر کافی است. برای پایداری بیشتر MySQL بهتر است.

## ورود پنل

- آدرس: `https://yourdomain.com/admin.html`
- نام کاربری: `admin`
- رمز اولیه: `8242026`

بعد از ورود، رمز را از تب «اطلاعات سایت» عوض کنید.

## مسیر فایل‌ها

- تصاویر ثابت: `public/images/`
- تصاویر پنل: `public/uploads/foods/`
- دیتابیس SQLite: `public/data/maha.db`
- تنظیمات: `public/.env`

## پشتیبان‌گیری

قبل از هر تغییر این‌ها را دانلود کنید:

- `.env`
- `data/maha.db` یا خروجی phpMyAdmin
- `uploads/foods/`

## اجرای محلی (اختیاری)

اگر PHP روی سیستم نصب باشد:

```bash
cd public
php -S localhost:8000 router.php
```

یا روی ویندوز فایل `start.bat` را اجرا کنید.

سپس باز کنید: [http://localhost:8000](http://localhost:8000)

روی ویندوز معمولاً XAMPP ساده‌تر است: محتویات `public` را در `htdocs` بگذارید.

## API

- `GET /api/health`
- `GET /api/settings`
- `GET /api/categories`
- `GET /api/foods`
- `GET /api/foods/{id}`
- `POST /api/auth/login`
- مسیرهای `/api/admin/...` بعد از ورود
