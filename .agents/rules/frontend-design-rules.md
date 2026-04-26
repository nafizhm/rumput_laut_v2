---
trigger: always_on
---

# Frontend UI System Guidelines

**Blade + Alpine.js**

This document defines the **standard UI system** for all dashboard and management interfaces.
It is **mandatory** for all developers to follow these conventions to ensure consistency, scalability, and maintainability.

---

## Core Principles

* **Consistency over creativity** → Do not invent new patterns if one already exists.
* **Component-first** → Always use predefined Blade components.
* **Single interaction pattern** → Same action = same UI + same behavior.
* **Modal over navigation** → Prefer modals for CRUD operations.
* **State is centralized** → All form state must be handled via Alpine `useForm()`.

---

## 1. Page Structure (Required)

Every page **must follow this layout**:

```blade
@extends('layouts.app')

@section('title', 'Page Name')

@section('breadcrumb')
    <x-breadcrumb 
        pageTitle="Page Name" 
        :links="[
            ['name' => 'Page Name', 'url' => route('route-name.index')]
        ]" 
    />
@endsection

@section('content')
    <!-- Main content -->
@endsection
```

### Rules

* Do not remove breadcrumb.
* Do not create custom layouts.
* Title must match breadcrumb.

---

## 2. Data Table Standard

All list views **must use**:

```blade
<x-data-table 
    id="tableId" 
    :rows="$data" 
    :columns="$columns" 
    :url="route('endpoint')" 
>
```

### 2.1 Header Action (Create Button)

```blade
<x-slot:headerAction>
    <button 
        type="button" 
        @click="$dispatch('create-entity')" 
        class="flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3"
    >
        <!-- lucide plus icon -->
        Add Data
    </button>
</x-slot:headerAction>
```

### Rules

* Label is always: **"Add Data"**
* Event naming: `create-entity`
* No custom button styles allowed

---

### 2.2 Row Actions

```blade
<x-slot:action>
    <div class="flex items-center gap-2">
        <button 
            type="button" 
            @click="$dispatch('edit-entity', item)" 
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-600"
        >
            Edit
        </button>

        <button 
            type="button" 
            @click="$dispatch('delete-entity', item.id)" 
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-rose-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-rose-600"
        >
            Delete
        </button>
    </div>
</x-slot:action>
```

### Rules

* Order is fixed: **Edit → Delete**
* No icons inside buttons
* No additional actions unless approved

---

### 2.3 Badge System

Use only these variants:

| Type    | Classes                                               |
| ------- | ----------------------------------------------------- |
| Success | `bg-green-50 text-green-700 ring-1 ring-green-600/20` |
| Danger  | `bg-red-50 text-red-700 ring-1 ring-red-600/20`       |
| Neutral | `bg-gray-50 text-gray-600 ring-1 ring-gray-500/10`    |

### Rules

* Do not create new color variants
* Always use `<span>`

---

## 3. Modal System (CRUD Standard)

All Create/Edit interactions **must use modal**.

```blade
<div x-data="formComponent()">
    <x-modal name="entity-modal">

        <h4 
            class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Data' : 'Add Data'"
        ></h4>

        <form @submit.prevent="submit">
            <div class="space-y-4">
                <!-- inputs -->
            </div>

            <div class="mt-6 flex justify-end gap-3">

                <button 
                    type="button" 
                    @click="$dispatch('close-modal', 'entity-modal')" 
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                    Cancel
                </button>

                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600"
                >
                    <span x-show="!form.processing">Save</span>
                    <span x-show="form.processing">Saving...</span>
                </button>

            </div>
        </form>
    </x-modal>

    <x-delete-modal
        name="delete-entity-modal"
        message="Are you sure you want to delete this data? This action cannot be undone."
        @confirm-delete-delete-entity-modal.window="deleteConfirmed"
    />
</div>
```

### Rules

* No separate pages for create/edit
* Modal name must follow: `entity-modal`
* Delete modal is **required**

---

## 4. Form Components

Only use system components:

* `<x-input>`
* `<x-select>`
* `<x-checkbox>`

### 4.1 Input

```blade
<x-input 
    label="Name"
    name="name"
    x-model="form.data.name"
    placeholder="Example: Admin"
    alpine-error="form.getError('name')"
/>
```

---

### 4.2 Select

```blade
<x-select
    label="Parent"
    name="parent_id"
    :options="$options"
    placeholder="Select parent"
    x-on:change="$dispatch('update-field', { field: 'parent_id', value: $event.detail })"
    alpine-error="form.getError('parent_id')"
/>
```

---

### 4.3 Checkbox

```blade
<x-checkbox 
    name="access_read"
    label="Read"
    model="form.data.access"
    value="read"
/>
```

---

### Rules

* No native `<input>` / `<select>` allowed
* Must bind to `form.data`
* Errors must use `form.getError()`

---

## 5. Alpine State Architecture

Every form component **must follow this contract**:

```js
function formComponent() {
    return {
        editMode: false,
        editId: null,
        deleteId: null,

        form: useForm({
            // fields
        }),

        init() {
            this.$watch(...)
            window.addEventListener(...)
        },

        create() {},

        edit(item) {},

        deleteItem(id) {},

        deleteConfirmed() {},

        submit() {}
    }
}
```

---

### Required Behaviors

#### Create

* Reset form
* Reset UI state (Select2, etc)
* Open modal

#### Edit

* Set `editMode = true`
* Bind data to `form.data`
* Open modal

#### Delete

* Store `deleteId`
* Open confirmation modal

#### Submit

* `POST` → create
* `PUT` → edit
* On success:

  * `refreshDataTable('tableId')`
  * close modal
  * show toast

---

## 6. Naming Convention (Strict)

| Type         | Format                                          |
| ------------ | ----------------------------------------------- |
| Event        | `create-entity`, `edit-entity`, `delete-entity` |
| Modal        | `entity-modal`                                  |
| Delete Modal | `delete-entity-modal`                           |
| Table ID     | `entityTable`                                   |

---

## 7. Anti-Patterns (Not Allowed)

* ❌ Creating custom UI outside system components
* ❌ Inline styles
* ❌ Different button variants
* ❌ Separate create/edit pages
* ❌ Mixing state outside `useForm()`
* ❌ Inconsistent event naming
* ❌ Skipping delete confirmation

---

## 8. Enforcement

Any PR that:

* Breaks component usage
* Introduces inconsistent UI
* Does not follow naming convention

→ **Must be rejected**
