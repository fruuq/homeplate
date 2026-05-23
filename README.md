# Homeplate — Backend API

**Stack:** PHP 8.1+ · MySQL 8+ · Apache (mod_rewrite)

---

## Setup

1. **Database**
   ```bash
   mysql -u root -p < schema.sql
   ```

2. **Environment** — create a `.env` file or set server env vars:
   ```
   DB_HOST=localhost
   DB_NAME=homeplate
   DB_USER=root
   DB_PASSWORD=yourpassword
   ```
   Load it in Apache via `SetEnv` or use a library like `vlucas/phpdotenv`.

3. **Default admin login**
   - Email: `admin@homeplate.com`
   - Password: `Admin@1234` ← **change immediately**

4. **Upload dirs** — make writable:
   ```bash
   chmod 755 uploads/meals uploads/avatars uploads/ids
   ```

5. **Stripe/PayPal** — fill real keys in `config/config.php`.

---

## API Reference

All responses are `application/json`. Auth via PHP sessions (cookie-based).

### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register customer |
| POST | `/auth/login` | Login |
| POST | `/auth/logout` | Logout |

**Register body:**
```json
{ "name": "Alice", "email": "a@b.com", "password": "Secure123!", "phone": "0791234567" }
```

---

### Meals
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/meals` | Public | Browse/search/filter |
| GET | `/api/meals/{id}` | Public | Meal detail + reviews |
| POST | `/api/meals` | Cook | Create meal |
| PUT | `/api/meals/{id}` | Cook | Update meal |
| DELETE | `/api/meals/{id}` | Cook/Admin | Soft-delete |
| PATCH | `/api/meals/{id}/availability` | Cook | Toggle available |

**GET /api/meals query params:**
- `q` — full-text search
- `category` — category ID
- `min_price`, `max_price`
- `cook_id`
- `sort` — `created_at` | `price` | `rating_avg`
- `page`

---

### Orders
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| POST | `/api/orders` | Customer | Place order |
| GET | `/api/orders` | Auth | List orders (scoped by role) |
| GET | `/api/orders/{id}` | Auth | Order detail |
| PATCH | `/api/orders/{id}/status` | Cook/Admin | Update status |
| DELETE | `/api/orders/{id}` | Customer/Admin | Cancel |

**Place order body:**
```json
{
  "delivery_address": "123 Main St",
  "items": [{"meal_id": 5, "quantity": 2}],
  "notes": "No onions please"
}
```

**Order statuses:** `pending → accepted → preparing → ready → out_for_delivery → delivered`

Customers can cancel within **5 minutes** of placing.

---

### Cooks
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| POST | `/api/cooks/apply` | Customer | Apply to become a cook |
| GET | `/api/cooks` | Public | List approved cooks |
| GET | `/api/cooks/{id}` | Public | Cook profile + meals |
| GET | `/api/cooks/dashboard` | Cook | Own stats |
| PATCH | `/api/cooks/{id}/verify` | Admin | Approve/reject |

---

### Reviews
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| POST | `/api/reviews` | Customer | Submit review (only after delivered order) |
| GET | `/api/reviews?meal_id=X` | Public | Get meal reviews |

---

### Messages
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/messages/conversations` | Inbox list |
| GET | `/api/messages?with={userId}` | Thread with user |
| POST | `/api/messages` | Send message |

---

### Notifications
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/notifications` | List notifications + unread count |
| PATCH | `/api/notifications/read` | Mark all as read |

---

### Favorites
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/favorites` | List favorites |
| POST | `/api/favorites` | Add `{ "meal_id": X }` |
| DELETE | `/api/favorites` | Remove `{ "meal_id": X }` |

---

### Profile
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/profile` | Get own profile |
| PUT | `/api/profile` | Update name/phone/avatar |
| PUT | `/api/profile/password` | Change password |
| DELETE | `/api/profile` | Deactivate or delete (`{ "action": "deactivate"|"delete" }`) |

---

### Reports
| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| POST | `/api/reports` | Auth | Submit report |
| GET | `/api/reports` | Admin | List reports |
| PATCH | `/api/reports/{id}` | Admin | Resolve/dismiss |

---

### Admin
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/dashboard` | Platform stats |
| GET | `/api/admin/users` | List users (filter by role/status/q) |
| PATCH | `/api/admin/users/{id}` | Set user status |

---

## Security Notes

- All DB queries use PDO prepared statements — no raw interpolation.
- Passwords hashed with bcrypt cost 12.
- Sessions use `httponly`, `samesite=Lax`; `secure` in production.
- File uploads validated by MIME type (not extension), size-capped at 5MB.
- PHP files blocked inside `/uploads/` via `.htaccess`.
- XSS/clickjack headers set via `.htaccess`.
- SQL injection: impossible — zero string concatenation into queries.
