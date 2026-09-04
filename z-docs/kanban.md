# POS Kanban

✅ - Done
❌ - Not started
⚠️ - Urgent and need to be worked on now
🔥 - System down, fix now

Authentication & Authorization

❌ Email and password authentication
❌ Role-based access control (Super Admin, Admin, Cashier) (urgent - blocks UI)
❌ Login/Logout functionality
❌ Session timeout after 30 mins of inactivity

Super Admins

❌ can CRUD all users
❌ can view system audit logs
❌ can reset any user's password
❌ can configure store settings (tax rates, store name, currency)

Admins

❌ can CRUD cashiers and admins
❌ can CRUD product categories
❌ can CRUD products
❌ can view all orders (filtered by date/cashier)
❌ can void/cancel orders (with reason)
❌ can generate sales reports (daily/weekly/monthly)
❌ can manage discounts/promotions (CRUD)

Cashiers

❌ can add products to cart and checkout to create an order (urgent - core MVP)
❌ can R products (view product list with search/filter) (urgent - core MVP)
❌ can view order history (their own transactions)
❌ can process returns/exchanges (with manager override for >$50)
❌ can apply discounts to cart (up to 10% without approval)
❌ can suspend/resume carts for later
❌ can print receipt (or generate PDF)

Order Management

❌ Order creation with line items, subtotal, tax, total (urgent)
❌ Payment processing (Cash, Card, QR) (urgent)
❌ Order status tracking (pending, paid, voided, refunded)
❌ Receipt generation with order #, date, items, totals
❌ Order numbering (auto-incrementing, resets daily or sequential)

Product Management

❌ Product CRUD with name, price, SKU, category, stock (urgent - data foundation)
❌ Stock tracking (deduct on sale) (urgent)
❌ Low stock alerts (when < 5 items)
❌ Bulk import/export (CSV)

Infrastructure

❌ Database schema & migrations (urgent)
❌ Basic error handling & validation (urgent)
❌ Responsive UI (works on tablet/desktop)
❌ Seed data for categories & demo products
❌ .env configuration for production
❌ Unit tests for core flows (auth, cart, checkout)
❌ Basic logging for debugging

Out of Scope (Post-MVP)

❌ Multi-store/branch support
❌ Customer loyalty program
❌ Advanced reporting dashboards
❌ Integration with accounting software
❌ Mobile app (PWA optional)
❌ Real-time inventory sync across stores

Immediate Sprint

❌ Database schema design
❌ JWT auth + RBAC
❌ Product CRUD + stock
❌ Cart + checkout + payment
❌ Basic product listing (R for cashiers)
❌ Deploy to staging
