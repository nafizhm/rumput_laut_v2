---
trigger: always_on
---

# Laravel Backend Architecture Guidelines

**Service Layer + Feature-Based Structure (Blade + Alpine)**

---

## Core Principles

- **Thin Controllers** → Controllers must not contain business logic
- **Service-Driven Logic** → All business logic lives in Services
- **Feature-Based Structure** → Organize code by feature (menu), not by type
- **Laravel Native First** → Use FormRequest for validation
- **Pragmatic Approach** → Avoid over-engineering (no Repository, no Resource)
- **Reusable Logic Only** → Use Traits and Helpers carefully

---

## 1. Folder Structure (Required)

All code must be grouped by feature (based on menu hierarchy):

```id="tqf9ns"
app/
├── Http/
│   ├── Controllers/
│   │   └── Transaction/
│   │       ├── PurchaseController.php
│   │       └── SalesController.php
│   │
│   ├── Requests/
│   │   └── Transaction/
│   │       ├── StorePurchaseRequest.php
│   │       ├── UpdatePurchaseRequest.php
│   │
├── Services/
│   └── Transaction/
│       ├── PurchaseService.php
│       └── SalesService.php
│
├── Traits/
│   ├── HandlesTransaction.php
│   ├── UploadFile.php
│
├── Helpers/
│   └── helper.php
```

---

### Rules

- Must follow **feature grouping**
- Do not mix unrelated features in the same folder
- Each feature must be isolated and self-contained

---

## 2. Controller Rules (Strict)

Controllers act only as a bridge between request and service.

```php id="7s50c4"
class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $service
    ) {}

    public function store(StorePurchaseRequest $request)
    {
        $this->service->store($request->validated());

        return response()->json([
            'message' => 'Created successfully'
        ]);
    }

    public function update(UpdatePurchaseRequest $request, $id)
    {
        $this->service->update($id, $request->validated());

        return response()->json([
            'message' => 'Updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $this->service->delete($id);

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}
```

---

### Rules

- ❌ No database queries inside controllers
- ❌ No business logic
- ❌ No data transformation
- ✅ Only:
    - call service
    - return response
    - use FormRequest

---

## 3. Service Layer (Required)

All business logic must live inside Services.

```php id="zyy2v9"
class PurchaseService
{
    use HandlesTransaction;

    public function store(array $data)
    {
        return $this->transaction(function () use ($data) {

            $purchase = Purchase::create($data);

            if (isset($data['items'])) {
                $purchase->items()->createMany($data['items']);
            }

            return $purchase;
        });
    }

    public function update($id, array $data)
    {
        return $this->transaction(function () use ($id, $data) {

            $purchase = Purchase::findOrFail($id);
            $purchase->update($data);

            return $purchase;
        });
    }

    public function delete($id)
    {
        $purchase = Purchase::findOrFail($id);
        $purchase->delete();
    }
}
```

---

### Rules

- All business logic must be here
- Direct model access is allowed
- Use transactions when:
    - multiple queries exist
    - relational data is involved

---

## 4. Form Request (Validation Layer)

Validation must be handled using Laravel FormRequest.

```php id="6pvydb"
class StorePurchaseRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
```

---

### Rules

- ❌ No validation inside Service
- ❌ No validation inside Controller
- ✅ All validation must be inside Request

---

## 5. Traits (Reusable Logic Only)

Traits are allowed only for reusable, non-business logic.

```php id="0h28f0"
trait HandlesTransaction
{
    public function transaction(callable $callback)
    {
        return DB::transaction($callback);
    }
}
```

---

### Rules

- Use for:
    - database transactions
    - file uploads
    - shared utility logic

- ❌ Do not store business logic inside Traits

---

## 6. Helpers (Strict Usage)

Helpers must be pure functions.

```php id="hoxrw6"
function formatRupiah($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
```

---

### Rules

- ❌ No database queries
- ❌ No side effects
- ❌ No business logic
- ✅ Only formatting / utility

---

## 7. Response Standard (Frontend Contract)

Backend must follow a consistent response format compatible with Alpine `useForm()`.

### Success Response

```json id="g61tqx"
{
    "message": "Success"
}
```

---

### Validation Error (Laravel Default)

```json id="c0yo0p"
{
    "errors": {
        "field": ["Error message"]
    }
}
```

---

### Rules

- Always return JSON for form actions
- Do not modify Laravel validation error structure
- Must be compatible with:
    - `form.errors`
    - `form.processing`
    - `form.wasSuccessful`

---

## 8. Naming Convention (Strict)

| Type       | Format                                          |
| ---------- | ----------------------------------------------- |
| Controller | `PurchaseController`                            |
| Service    | `PurchaseService`                               |
| Request    | `StorePurchaseRequest`, `UpdatePurchaseRequest` |
| Trait      | `HandlesTransaction`                            |
| Helper     | `formatSomething()`                             |

---

## 9. Anti-Patterns (Forbidden)

- ❌ Business logic in Controller
- ❌ Queries in Controller
- ❌ Validation outside FormRequest
- ❌ Business logic in Trait
- ❌ Database access in Helper
- ❌ Repository / DTO / unnecessary abstraction
- ❌ Over-engineering

---

## 10. Enforcement Rules

Any pull request must be rejected if:

- Controller is not thin
- Logic is not placed in Service
- FormRequest is not used
- Folder structure is not feature-based
- Code introduces inconsistency

---

## 11. Frontend Compatibility (Alpine useForm)

Backend must work seamlessly with this pattern:

- `422` → validation errors → auto handled
- `200` → success → triggers `onSuccess`
- `processing` state handled automatically

---

## Final Note

This architecture is designed for:

- Medium-scale applications
- Fast development speed
- Clean and maintainable code
- Minimal complexity with maximum clarity
