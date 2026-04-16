# 🍽️ Restaurant Management System (Bangladesh SaaS)

A complete **enterprise-level multi-branch restaurant management system** built for Bangladeshi businesses using modern technologies.

---

## 🚀 Tech Stack

### Backend

- Laravel (Latest) 
- MySQL
- Laravel Sanctum (Authentication)

### Frontend

- React.js (with Inertia.js)
- Redux Toolkit (State Management)
- Tailwind CSS (UI Styling)

---

## 🎯 Key Features

### 🧾 POS (Point of Sale)

- Dine-in / Takeaway / Delivery
- Split billing
- Discount (Fixed & Percentage)
- VAT calculation (5%, 7.5%, 10%)
- Payment methods:
    - Cash
    - Card
    - bKash / Nagad / Rocket

---

### 🏢 Multi-Branch System

- One company → multiple branches
- Each branch has:
    - Separate inventory
    - Separate employees
    - Independent reports

---

### 🍽️ Table & QR Ordering

- Unique QR code for each table
- Customers scan → view menu → place order
- Real-time order submission

---

### 🍳 Kitchen Display System (KDS)

- Live order tracking
- Status:
    - Pending
    - Cooking
    - Ready

---

### 📦 Inventory Management

- Raw materials tracking
- Recipe-based deduction
- Auto stock reduction
- Low stock alerts

---

### 💰 Accounting System

- Income & Expense tracking
- Ledger system:
    - Customer Ledger
    - Supplier Ledger

- Trial Balance
- Profit & Loss
- Cash flow tracking

---

### 👨‍💼 Employee Management

- Role-based access:
    - Admin
    - Manager
    - Cashier
    - Waiter
    - Kitchen Staff

- Attendance tracking
- Salary management

---

### 🎁 Offers & Promotions

- Coupon system
- Buy 1 Get 1
- Discount rules
- Loyalty points

---

### 👤 Customer CRM

- Customer profiles
- Order history
- Loyalty tracking

---

### 📊 Reports & Analytics

- Daily sales reports
- Branch performance
- Top selling items
- Expense reports

---

### 🇧🇩 VAT & Tax (Bangladesh)

- Configurable VAT rates
- VAT reports
- Bangladesh-compliant calculation

---

## 🎨 UI Design System

- Light SaaS dashboard style
- Tailwind CSS based
- Color Palette:
    - Primary: Purple → Indigo → Blue gradient
    - Accent: Teal / Cyan
    - Background: `#F8FAFC`
    - Cards: White with soft shadows

- Components:
    - Rounded cards (`rounded-2xl`)
    - Gradient buttons
    - KPI dashboard widgets

---

## 🔐 Authentication & Authorization

- Laravel Sanctum (API auth)
- Role-Based Access Control (RBAC)
- Branch-level data restriction

---

## ⚡ Performance Optimization

- Eager loading (Laravel ORM)
- Query optimization
- Caching (Redis recommended)
- Lazy loading on frontend

---

## 🔄 System Flow

Customer → Order → Kitchen → Serve → Payment → Accounting → Reports

---

## 🧠 Project Structure

```
backend/
├── app/
│   ├── Models/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   ├── Services/
│   ├── Events/
│   ├── Listeners/
│
├── database/
│   ├── migrations/
│   ├── seeders/

frontend/
├── resources/js/
│   ├── Pages/
│   ├── Components/
│   ├── Layouts/
│   ├── Redux/
│
├── routes/
│   ├── web.php
│   ├── api.php
```

---

## ⚙️ Installation Guide

### 1. Clone Project

```bash
git clone <repo-url>
cd restaurant-management
```

---

### 2. Backend Setup (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Update `.env`:

```
DB_DATABASE=restaurant
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate
php artisan serve
```

---

### 3. Frontend Setup

```bash
cd frontend
npm install
npm run dev
```

---

## 🔌 API Authentication (Sanctum)

- Login → get token
- Use token in headers:

```
Authorization: Bearer <token>
```

---

## 📡 Real-time Features

Recommended:

- Laravel Broadcasting (Pusher / WebSockets)

Fallback:

- Polling (API interval calls)

---

## 🧪 Testing

```bash
php artisan test
```

---

## 📦 Deployment

### Backend

- Nginx / Apache
- PHP 8.2+
- MySQL

### Frontend

- Build:

```bash
npm run build
```

---

## 🔥 Future Enhancements

- Mobile App (React Native)
- AI Sales Insights
- Advanced analytics dashboard
- Multi-tenant SaaS billing system

---

## 👨‍💻 Author

Built for Bangladeshi Restaurant Businesses 🇧🇩

---

## 📄 License

This project is proprietary / commercial SaaS.

---

✨ Ready to scale your restaurant business with technology!
