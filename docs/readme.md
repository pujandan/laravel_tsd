# Laravel AI Documentation - Universal Guide

Welcome to the universal Laravel documentation. This guide provides AI assistants with comprehensive coding standards and patterns for Laravel development using this architecture.

> **Note:** This documentation is designed to be **project-agnostic** and can be used across different Laravel projects. For domain-specific examples, see the [domains](./domains/) directory.

---

## 📚 Quick Learning Path for AI

**When user says "pelajari /docs", follow this sequence:**

| Step | File to Read | Purpose | Mandatory? |
|------|-------------|---------|------------|
| 1 | `/docs/readme.md` | Architecture overview & structure | ✅ YES |
| 2 | `/docs/ai/quick-reference.md` | ALL coding rules (15 sections) | ✅ YES |
| 3 | `/docs/ai/templates.md` | Implementation templates | ✅ YES |
| 4 | `/docs/ai/checklist.md` | Validation checklist | ✅ YES |
| 5 | `/docs/patterns/*.md` | Deep dive patterns (9 files) | ⏸️ As needed |
| 6 | `/docs/domains/*/readme.md` | Domain examples (4 domains) | ⏸️ As needed |

**After Steps 1-4, confirm with:**
```
✅ Saya sudah mempelajari dokumentasi Laravel AI:
- Architecture: Service Layer Pattern (Controller → Service → Model)
- Critical rules: Interface mandatory, DB::transaction, AppResponse::success(JsonResource, message)
- Core docs: /docs/readme.md, /docs/ai/quick-reference.md, /docs/ai/templates.md, /docs/ai/checklist.md
- Patterns: /docs/patterns/ (9 files)
- Domains: /docs/domains/ (4 domains)

Siap menerima tugas coding!
```

---

## 📋 Project Context

**Framework:** Laravel 12.49.0
**Language:** PHP 8.3
**Database:** MySQL with UUID primary keys
**Architecture:** Service Layer Pattern (Controller → Service → Model)
**Frontend:** Livewire 4.1.0 + Tailwind CSS 4
**Current Year:** 2026

---

## 🚀 Quick Start for New Chat

### Simple Prompt (Recommended)

Just say:
```
pelajari /docs
```

**AI will follow this learning sequence:**

1. **Read main entry point** → `docs/readme.md` (this file)
   - Understand architecture overview
   - Learn file structure

2. **Read core rules** → `docs/ai/quick-reference.md`
   - All naming conventions
   - Controller, Service, Model rules
   - Response format, transaction, error handling patterns
   - Common mistakes to avoid

3. **Read implementation guide** → `docs/ai/templates.md`
   - Controller template
   - Service interface & implementation templates
   - Model, Request, Resource templates

4. **Read validation guide** → `docs/ai/checklist.md`
   - What to check before committing code

5. **(Optional) Read pattern documentation** → `docs/patterns/`
   - Only when needed for deep understanding
   - `service-layer.md` - Service layer pattern details
   - `database-transaction.md` - Transaction pattern
   - `error-handling.md` - Exception handling
   - Others as needed

6. **(Optional) Read domain examples** → `docs/domains/`
   - Only when working on specific domain
   - `ecommerce/` - Products, Orders examples
   - `hr/` - Employees, Leave examples
   - `tourism/` - Packages, Bookings examples
   - `satiket/` - Multi-vertical ticketing examples

7. **Confirm understanding** by responding with:
   ```
   ✅ Saya sudah mempelajari dokumentasi Laravel AI:
   - Architecture: Service Layer Pattern (Controller → Service → Model)
   - Critical rules: Interface mandatory, DB::transaction, AppResponse::success(JsonResource, message)
   - Documentation: /docs/readme.md, /docs/ai/quick-reference.md, /docs/ai/templates.md, /docs/ai/checklist.md
   - Patterns: /docs/patterns/ (9 files)
   - Domains: /docs/domains/ (4 domains)

   Siap menerima tugas coding. Silakan berikan instruksi!
   ```

8. **Wait for user's task** before proceeding

---

## 🤖 For AI Assistant

### When User Says "pelajari /docs"

Follow this **exact learning sequence**:

#### Step 1: Read Main Documentation
```
1. Read /docs/readme.md (this file) - COMPLETELY
   - Understand architecture
   - Note file structure
```

#### Step 2: Read Core Rules (MANDATORY)
```
2. Read /docs/ai/quick-reference.md - ALL 15 sections
   - Naming conventions (Section 1)
   - Controller rules (Section 2)
   - Service rules (Section 3)
   - Model rules (Section 4)
   - Request/Resource rules (Section 5-6)
   - Route/Migration rules (Section 7-8)
   - Response format (Section 9)
   - Transaction pattern (Section 10)
   - Error handling (Section 11)
   - Model retrieval (Section 12)
   - Enum pattern (Section 13)
   - Common mistakes (Section 14)
   - Safe execution (Section 15)
```

#### Step 3: Read Templates (MANDATORY)
```
3. Read /docs/ai/templates.md - ALL templates
   - Controller template
   - Service interface template
   - Service implementation template
   - Model template
   - Request templates
   - Resource templates
   - Migration template
```

#### Step 4: Read Validation Guide (MANDATORY)
```
4. Read /docs/ai/checklist.md - ALL sections
   - Service layer checklist
   - Controller checklist
   - Model checklist
   - Request/Resource checklist
   - Migration checklist
   - General checklist
   - Common mistakes
```

#### Step 5: Read Pattern Documentation (AS NEEDED)
```
5. Read /docs/patterns/ ONLY when deep understanding needed:
   - patterns/service-layer.md - Service layer details
   - patterns/database-transaction.md - Transaction pattern
   - patterns/error-handling.md - Exception handling
   - patterns/safe-execution.md - AppSafe helper
   - patterns/enum-pattern.md - Enum usage
   - patterns/model-retrieval.md - findOrFail vs find
   - patterns/reusable-relationships.md - Relationship patterns
   - patterns/livewire.md - Livewire 4 components
   - patterns/snapshot-pattern.md - Snapshot pattern
   - patterns/app-has-images.md - Image upload management
   - patterns/filament-resource.md - Filament v5 Resource pattern
```

#### Step 6: Read Domain Examples (AS NEEDED)
```
6. Read /docs/domains/ ONLY when working on specific domain:
   - domains/ecommerce/readme.md - E-commerce examples
   - domains/hr/readme.md - HR/Workforce examples
   - domains/tourism/readme.md - Tourism examples
   - domains/satiket/readme.md - Multi-vertical ticketing
```

#### Step 7: Confirm Understanding
After completing Steps 1-4 (MANDATORY), respond with:
```
✅ Saya sudah mempelajari dokumentasi Laravel AI:
- Architecture: Service Layer Pattern (Controller → Service → Model)
- Critical rules: Interface mandatory, DB::transaction, AppResponse::success(JsonResource, message)
- Core documentation: /docs/readme.md, /docs/ai/quick-reference.md, /docs/ai/templates.md, /docs/ai/checklist.md
- Pattern reference: /docs/patterns/ (9 files available)
- Domain examples: /docs/domains/ (4 domains available)

Siap menerima tugas coding. Silakan berikan instruksi!
```

#### Step 8: Wait for Task
Wait for user's instruction before reading optional files (Steps 5-6).

---

## Architecture Overview

### Core Pattern: Service Layer Pattern

This project follows the **Service Layer Pattern** with clear separation of concerns:

```
HTTP Request → Controller → Service → Model → Database
                  ↓           ↓
             Transactions  Business Logic
             & JSON        & Validation
```

### Layer Responsibilities

| Layer | Purpose | MUST DO | MUST NOT DO |
|-------|---------|---------|-------------|
| **Controller** | HTTP handling | • Inject Service via constructor<br>• Use DB::transaction() for write operations<br>• Return via AppResponse::success(JsonResource, message)<br>• Use __() for locale | • Business logic<br>• Try-catch blocks<br>• Direct Model calls (use service) |
| **Service** | Business logic | • Have Interface (mandatory)<br>• Implement Interface<br>• Use AppTransactional trait<br>• Call $this->requireTransaction() in write methods<br>• Handle all business rules and validation | • DB::transaction()<br>• HTTP concerns<br>• Return JSON responses |
| **Model** | Data access | • Use HasUuids trait for UUID primary keys<br>• Use AppAuditable trait for audit trails<br>• Use guarded instead of fillable<br>• Relations in camelCase<br>• Use SoftDeletes if deleted_by needed | • Business logic<br>• Direct return to HTTP |

---

## File Structure Guide

### AI Documentation (docs/ai/)

| File | Purpose | When to Read |
|------|---------|--------------|
| ai/README.md | AI documentation guide | First time reading |
| ai/quick-reference.md | All rules in detail | Every coding task |
| ai/templates.md | Implementation templates | When creating code |
| ai/checklist.md | Validation checklist | Before committing |

### Pattern Documentation (docs/patterns/)

| File | Deep Dive Into |
|------|----------------|
| patterns/service-layer.md | Service layer pattern, interfaces, traits |
| patterns/database-transaction.md | Transaction pattern, AppTransactional trait |
| patterns/error-handling.md | Exception handling, logging philosophy |
| patterns/safe-execution.md | AppSafe helper for silent failures (emails, webhooks, etc.) |
| patterns/enum-pattern.md | Enum usage and best practices |
| patterns/model-retrieval.md | findOrFail vs find pattern |
| patterns/reusable-relationships.md | Reusable relationship patterns |
| patterns/livewire.md | Livewire 4 components and best practices |
| patterns/snapshot-pattern.md | Snapshot pattern |
| patterns/app-has-images.md | Image upload management (AppHasImages trait) |
| patterns/app-has-images-setup.md | MinIO/S3 setup guide for AppHasImages |
| patterns/filament-resource.md | Filament v5 Resource pattern (admin panel) |
| patterns/email-service.md | Email service dengan queue, retry, multi-driver (SMTP/Mailtrap) |

### Domain Examples (docs/domains/)

| Directory | Domain | Examples |
|-----------|--------|----------|
| domains/ecommerce/ | E-commerce | Products, Orders, Inventory |
| domains/hr/ | HR/Workforce | Employees, Leave, Payroll |
| domains/tourism/ | Tourism/Travel | Packages, Bookings, Travelers |

### Other Documentation

| File | Purpose |
|------|---------|
| design-system.md | Design System (colors, typography, components) - configurable per project |
| crud_template.md | CRUD operation quick reference |

---

## Quick Command Reference for AI

When user asks you to:
- **"Create CRUD"** → Read crud_template.md + ai/templates.md
- **"Create controller"** → Read ai/templates.md#controller-template
- **"Create service"** → Read ai/templates.md#service-templates
- **"Create Livewire component"** → Read patterns/livewire.md
- **"Create Filament Resource"** → Read patterns/filament-resource.md
- **"Code review"** → Read ai/checklist.md
- **"Refactor code"** → Read ai/quick-reference.md + relevant pattern files
- **"Fix this error"** → Check ai/checklist.md + relevant pattern files

---

## 📝 How to Add New Rules (For AI)

When user asks you to **"add this rule to docs"** or **"update docs with new rule"**, follow this guide:

### Step 1: Identify Rule Type

| Rule Type | File to Update | Section |
|-----------|---------------|---------|
| **Naming convention** | `ai/quick-reference.md` | Section 1: Naming Conventions |
| **Controller rule** | `ai/quick-reference.md` | Section 2: Controller Rules |
| **Service rule** | `ai/quick-reference.md` | Section 3: Service Rules |
| **Model rule** | `ai/quick-reference.md` | Section 4: Model Rules |
| **Request/Resource rule** | `ai/quick-reference.md` | Section 5-6 |
| **Route/Migration rule** | `ai/quick-reference.md` | Section 7-8 |
| **Response format rule** | `ai/quick-reference.md` | Section 9 |
| **Transaction rule** | `ai/quick-reference.md` | Section 10 |
| **Error handling rule** | `ai/quick-reference.md` | Section 11 |
| **Model retrieval pattern** | `ai/quick-reference.md` | Section 12 |
| **Enum rule** | `ai/quick-reference.md` | Section 13 |
| **New pattern** | `patterns/{new-pattern}.md` | Create new file |

### Step 2: Update Files

**For simple rules (naming, coding standards):**
```bash
# Update ai/quick-reference.md
- Add rule to appropriate section
- Update tables/lists if needed
- Add example if helpful
```

**For new patterns (complex concepts):**
```bash
# Create new file in patterns/
- Create patterns/{pattern-name}.md
- Follow pattern template from existing files
- Add reference in this README (File Structure Guide)
```

**For template updates:**
```bash
# Update ai/templates.md
- Add new template if needed
- Update existing template with new rule
- Add usage example
```

**For checklist updates:**
```bash
# Update ai/checklist.md
- Add new checklist item
- Add to appropriate section (Service/Controller/Model/etc.)
- Add to Common Mistakes if applicable
```

### Step 3: Verify Updates

After adding new rule:
- [ ] Update `ai/quick-reference.md` with the rule
- [ ] Update `ai/templates.md` if affects templates
- [ ] Update `ai/checklist.md` with validation item
- [ ] Update `patterns/{pattern}.md` if it's a new pattern
- [ ] Update this README's "File Structure Guide" if new pattern file added

### Example: Adding New Rule

**User says:** "Add rule: Controller methods must not exceed 20 lines"

**AI Process:**
1. Identify: Controller rule
2. Update: `ai/quick-reference.md` Section 2 (Controller Rules)
3. Add rule to table: "Methods must not exceed 20 lines"
4. Update: `ai/templates.md` - Add note in controller template
5. Update: `ai/checklist.md` - Add to Controller checklist
6. Result: Rule now documented in all relevant places

---

## 🎨 Design System

The design system uses **configurable color themes**. See `design-system.md` for:
- Primary, Secondary, and Accent color configuration
- Typography system
- Component library (buttons, cards, forms, alerts)
- Usage examples for different business domains

---

## 🌐 Domain-Specific Examples

For implementation examples in specific business domains, see:
- **E-commerce:** `docs/domains/ecommerce/` - Products, Orders, Inventory
- **HR:** `docs/domains/hr/` - Employees, Leave, Payroll
- **Tourism:** `docs/domains/tourism/` - Packages, Bookings, Travelers

Each domain includes:
- Business logic examples
- Entity patterns
- Enum examples
- AppSafe patterns
- Common relationships

---

**Version:** 2.0 (Generic/Universal)
**Last Updated:** 2026-03-19
**Maintained by:** Development Team