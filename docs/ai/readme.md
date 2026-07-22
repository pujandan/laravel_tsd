# AI Documentation Guide

This folder contains AI-optimized documentation for coding assistance in Laravel applications using the laravel_tsd package.

> **⚡ IMPORTANT:** Ketika user meminta membuat fitur "sesuai pattern laravel_tsd", baca [`../AI_INSTRUCTIONS.md`](../AI_INSTRUCTIONS.md) dulu untuk instruksi lengkap.

---

### Reading Order for AI Assistant

When user asks you to "pelajari docs" or "learn the codebase":

1. **Start with:** `../README.md` (Main documentation entry)
2. **Then read:** `quick-reference.md` (All rules in detail - 17 sections)
3. **Then read:** `api-guide.md` (API CRUD template + pattern)
4. **Use:** `templates.md` (When creating code with AppHasImages or need examples)
5. **Validate:** `checklist.md` (Before committing)

---

## 📖 File Descriptions

### 1. quick-reference.md

**Purpose:** Complete reference of all coding rules and patterns

**Contains:**
- Naming conventions (detailed tables)
- Layer-specific rules (Controller, Service, Model, Request, Resource, Route)
- Response format rules
- Transaction pattern rules
- Error handling rules
- Model retrieval patterns
- Enum patterns
- All forbidden patterns

**When to read:**
- Every coding task
- When you need to check specific rules
- When validating code

**Key sections:**
- All naming conventions in tables
- Controller rules (10+ rules)
- Service rules (8+ rules)
- Model rules (6+ rules)
- Request/Resource rules
- Common mistakes

---

### 2. templates.md

**Purpose:** Implementation templates for all layers

**Contains:**
- Controller template (full CRUD)
- Service Interface template
- Service Implementation template
- Model template
- Request templates (IndexRequest, FormRequest)
- Resource templates (Resource, Collection)
- Migration template

**When to read:**
- When creating new controller
- When creating new service
- When creating new model
- When creating CRUD feature

**How to use:**
1. Copy template for the layer you need
2. Replace placeholders (e.g., {Entity}, {Module})
3. Customize business logic as needed
4. Validate with checklist.md

---

### 3. api-guide.md

**Purpose:** Complete API CRUD implementation guide

**Contains:**
- File structure overview
- Basic templates for 7 files (Controller, Interface, Service, Request x2, Resource x2)
- Pattern & conventions (transaction, named parameters, response format)
- Helper classes reference from laravel-tsd
- Route definition
- Pre-commit checklist

**When to read:**
- When creating new CRUD API
- When learning laravel-tsd pattern
- When need quick reference for API implementation

**How to use:**
1. Copy templates for each layer
2. Replace placeholders ({Entity}, {Module})
3. Follow patterns & conventions
4. Use helper classes from package
5. Validate with checklist

---

### 4. templates.md

**Purpose:** Advanced templates with domain examples

**Contains:**
- AppHasImages template (image upload pattern)
- Domain examples (E-commerce, HR, User)
- Template customization guide
- Model with image fields

**When to read:**
- When entity has image upload (use AppHasImages)
- When need domain-specific examples
- When need AppHasImages pattern

**How to use:**
1. Reference AppHasImages section for image upload
2. Check domain examples for patterns
3. Customize templates as needed

---

### 5. checklist.md

**Purpose:** Validation checklist for code quality

**Contains:**
- Service layer checklist (10+ items)
- Controller checklist (10+ items)
- Model checklist (8+ items)
- Request/Resource checklist
- Migration checklist
- General checklist

**When to read:**
- Before committing code
- During code review
- When validating implementation

**How to use:**
- Go through checklist item by item
- Mark each item as completed
- Fix any items that don't pass
- All items must be checked before commit

---

## 🎯 Common AI Tasks

### Task 1: Create New CRUD Feature

**Files to read:**
1. `api-guide.md` (Complete guide + templates)
2. `quick-reference.md` (Rules reference)
3. `checklist.md` (Validation)

**Process:**
1. Read naming rules from `quick-reference.md`
2. Copy templates from `api-guide.md` (7 files)
3. Customize business logic
4. Validate with `checklist.md`

---

### Task 2: Create CRUD with Image Upload

**Files to read:**
1. `api-guide.md` (Basic templates)
2. `templates.md` (AppHasImages section)
3. `../patterns/app-has-images.md` (Detailed pattern)
4. `checklist.md` (Validation)

**Process:**
1. Copy basic templates from `api-guide.md`
2. Add AppHasImages trait to model
3. Add image upload to controller/service
4. Generate URL dynamically in resource
5. Validate with `checklist.md`

---

### Task 3: Create Controller Only

**Files to read:**
1. `quick-reference.md` (section: Controller Rules)
2. `api-guide.md` (Controller template)

**Process:**
1. Check controller rules in `quick-reference.md`
2. Copy controller template from `api-guide.md`
3. Ensure all methods follow patterns
4. Validate with `checklist.md` (Controller section)

---

### Task 4: Create Service Only

**Files to read:**
1. `quick-reference.md` (section: Service Rules)
2. `api-guide.md` (Service templates)

**Process:**
1. Check service rules in `quick-reference.md`
2. Copy service interface template from `api-guide.md`
3. Copy service implementation template from `api-guide.md`
4. Ensure all methods follow patterns
5. Validate with `checklist.md` (Service section)

---

### Task 5: Code Review

**Files to read:**
1. `checklist.md` (Primary reference)
2. `quick-reference.md` (For rule verification)

**Process:**
1. Read through `checklist.md` item by item
2. Check each item against the code
3. Reference `quick-reference.md` for rule details
4. Report any items that don't pass

---

### Task 6: Fix Error or Bug

**Files to read:**
1. `checklist.md` (Check violations)
2. `quick-reference.md` (Understand correct pattern)
3. `../patterns/{relevant-pattern}.md` (If deep understanding needed)

**Process:**
1. Use `checklist.md` to identify what's wrong
2. Reference `quick-reference.md` for correct pattern
3. If needed, read detailed pattern documentation
4. Apply fix and validate with `checklist.md`

---

## 🔍 Quick Rule Lookup

### Naming Convention Lookup

Need to know naming convention for something?

| Need to name... | Go to section... |
|----------------|------------------|
| Database table | `quick-reference.md` → Naming → Database |
| Model class | `quick-reference.md` → Naming → Model |
| Controller class | `quick-reference.md` → Naming → Controller |
| Service class | `quick-reference.md` → Naming → Service |
| Route URL | `quick-reference.md` → Naming → Route |
| Model method/relation | `quick-reference.md` → Naming → Model |

### Pattern Lookup

Need to understand a specific pattern?

| Pattern | Reference |
|---------|-----------|
| Service layer | `quick-reference.md` → Service Rules OR `../patterns/service-layer.md` |
| Transaction | `quick-reference.md` → Transaction Pattern OR `../patterns/database-transaction.md` |
| Error handling | `quick-reference.md` → Error Handling OR `../patterns/error-handling.md` |
| Model retrieval | `quick-reference.md` → Model Retrieval OR `../patterns/model-retrieval.md` |
| Enum usage | `quick-reference.md` → Enum Pattern OR `../patterns/enum-pattern.md` |

### Template Lookup

Need template for specific layer?

| Layer | Template location |
|-------|-------------------|
| Controller | `templates.md` → Controller Template |
| Service Interface | `templates.md` → Service Interface Template |
| Service Implementation | `templates.md` → Service Implementation Template |
| Model | `templates.md` → Model Template |
| Request (Index) | `templates.md` → Index Request Template |
| Request (Form) | `templates.md` → Form Request Template |
| Resource (Single) | `templates.md` → Resource Template |
| Resource (Collection) | `templates.md` → Collection Template |
| Migration | `templates.md` → Migration Template |

---

## ⚠️ Common Mistakes to Watch

Before creating or modifying code, always check these common mistakes:

1. **Response parameter order** → `quick-reference.md` → Response Format
2. **Using Lang::get()** → `quick-reference.md` → Locale Messages
3. **Try-catch in controller** → `quick-reference.md` → Error Handling
4. **Snake_case relations** → `quick-reference.md` → Model Rules
5. **Missing @throws Throwable** → `quick-reference.md` → Controller Rules
6. **Service without interface** → `quick-reference.md` → Service Rules
7. **DB::transaction in service** → `quick-reference.md` → Transaction Pattern

---

## 📋 Summary

### For Quick Reference
→ Read: `quick-reference.md`

### For Implementation
→ Read: `templates.md`

### For Validation
→ Read: `checklist.md`

### For Deep Understanding
→ Read: `../patterns/{pattern}.md`

---

**Remember:** Always validate your code with `checklist.md` before considering it complete!

---

**Last Updated:** 2026-01-29

---

## 📝 How to Add New Rules (For AI)

When user asks you to **"add this rule to docs"** or **"update docs with new rule"**, follow this guide:

### Quick Reference: Which File to Update?

| Rule Type | File to Update | Section in quick-reference.md |
|-----------|---------------|------------------------------|
| **Naming convention** | `quick-reference.md` | Section 1 |
| **Controller rule** | `quick-reference.md` | Section 2 |
| **Service rule** | `quick-reference.md` | Section 3 |
| **Model rule** | `quick-reference.md` | Section 4 |
| **Request/Resource rule** | `quick-reference.md` | Section 5-6 |
| **Route/Migration rule** | `quick-reference.md` | Section 7-8 |
| **Response format rule** | `quick-reference.md` | Section 9 |
| **Transaction rule** | `quick-reference.md` | Section 10 |
| **Error handling rule** | `quick-reference.md` | Section 11 |
| **Model retrieval pattern** | `quick-reference.md` | Section 12 |
| **Enum rule** | `quick-reference.md` | Section 13 |
| **New complex pattern** | `../patterns/{new-pattern}.md` | Create new file |

### Step-by-Step Process

**Step 1: Identify rule type**
- Check if it's naming, coding standard, or new pattern
- Use table above to determine file to update

**Step 2: Update the appropriate file(s)**
- For simple rules: Add to `quick-reference.md` appropriate section
- For new patterns: Create new file in `../patterns/`
- Always add to `checklist.md` for validation
- Update `templates.md` if affects code templates

**Step 3: Update related files**
- Add validation item to `checklist.md`
- Update `templates.md` if template needs change
- Add to `Common Mistakes` in `quick-reference.md` if applicable

**Step 4: Verify**
- Rule is in `quick-reference.md`
- Validation item in `checklist.md`
- Template updated in `templates.md` (if needed)
- Pattern doc in `../patterns/` (if complex pattern)

### Example

**User says:** "Add rule: All service methods must have return type declarations"

**AI Process:**
1. Identify: Service rule → Section 3 in quick-reference.md
2. Update `quick-reference.md`: Add rule to Section 3 table
3. Update `checklist.md`: Add to Service layer checklist
4. Update `templates.md`: Add note in service template
5. Result: Rule documented everywhere

---

**Remember:** Always update ALL related files to maintain consistency!