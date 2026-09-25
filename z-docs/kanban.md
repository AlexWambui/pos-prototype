# POS Kanban

✅ - Done
❌ - Not started
⚠️ - Urgent and need to be worked on now
🔥 - System down, fix now

Authentication & Authorization

❌ Session timeout after 30 mins of inactivity
✅ Email and password authentication
✅ Role-based access control (Super Admin, Admin, Cashier) (urgent - blocks UI)
✅ Login/Logout functionality

Super Admins

❌ can view system audit logs
❌ can configure store settings (tax rates, store name, currency)
✅ can CRUD all users
✅ can reset any user's password
✅ can view dashboard sales summary (total revenue, total cogs, gross profit, gross profit margin, aov).
✅ can view dashboard payment summary (mpesa sales, cash sales).

Admins

✅ can login and view the admin dashboard
✅ can view dashboard sales summary (total revenue, total cogs, gross profit, gross profit margin, aov).
✅ can view dashboard payment summary (mpesa sales, cash sales).
✅ can CRUD users (cashiers, admins)
✅ can view all orders
✅ can filter orders by date, cashier and time
⚠️ can void/cancel orders (with reason)
❌ can generate sales reports (daily/weekly/monthly)
❌ can manage discounts/promotions (CRUD)
❌ can print sales receipts for paid and unpaid order
❌ can search payment according to amount or transaction code then link to an order.

Cashiers

❌ can keep track or when the shift starts or ends.
✅ can view dashboard with todays sales stats.
✅ can view dashboard with payment summary (mpesa sales, cash sales).
✅ can add products to cart and checkout to create an order (urgent - core MVP)
✅ can R products (view product list with search/filter) (urgent - core MVP)
✅ can view order history (their own transactions on the create order page)
❌ can process returns/exchanges (with manager override for > $50)
❌ can apply discounts to cart (up to 10% without approval)
❌ can suspend/resume carts for later
❌ can print receipt (or generate PDF)
❌ can print sales receipts for paid and unpaid order
❌ can search payment according to amount or transaction code then link to an order.
❌ can split orders between two people or merge to become for one person.
✅ can split payments to show payments made by cash or mpesa or card.
❌ can search payment according to amount or transaction code then link to an order.

Customers

❌ Can view their dashboard after they login
❌ can view the list of orders they have recently made

Order Management

❌ Payment processing (Cash, Card, QR) (urgent)
❌ Payments can be adjusted or updated.
❌ Receipt generation with order #, date, items, totals
✅ add a look up using phone number for customers when creating an order in the create order page so that the fields for adding customer details can be removed
✅ make order status and delivery status default to completed and picked up to make it easier to process POS orders
✅ Order quantity adjustment in the create order page
✅ Order creation with line items, subtotal, tax, total (urgent)
✅ Order status tracking (pending, paid, voided, refunded)
✅ Order numbering (auto-incrementing, resets daily or sequential)

Product Management

❌ Products can be easily searched for (using: product.name or product.barcode)
✅ Inventory / stock management
❌ Low stock alerts (when < 5 items)
❌ Bulk import/export (CSV)
🔥 product track_inventory attribute can be updated to true or false
🔥 notification for when products have low stock
✅ Product categories CRUD
✅ Product CRUD with name, price, SKU, category (urgent - data foundation)
✅ Stock tracking (deduct on sale) (urgent)
✅ product edit and update pages should use uuid instead of just id

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
