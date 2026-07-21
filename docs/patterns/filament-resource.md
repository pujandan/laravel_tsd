# Filament Resource Pattern

**Overview:** Complete guide for creating Filament v5 Resources based on existing Services. This pattern follows the architecture: Service → Model → Resource, ensuring consistency between API and Filament admin panel.

---

## 🎯 When to Use This Pattern

Use this pattern when:
- Creating a new Filament Resource for an existing Service/Model
- The Service already follows the Service Layer Pattern (with Interface)
- The Model uses standard traits (HasUuids, AppAuditable, AppHasImages, etc.)

**Prerequisites:**
- Service Interface and Service Implementation already exist
- Model already exists with proper traits
- Form Request and API Resource already exist (for reference)

---

## 🚀 Quick Start (AI Instructions)

**When user says:** "Create Filament Resource for [Entity]"

Follow this sequence:

### Step 1: Read Existing Service & Model
```bash
# Read the service interface to understand the structure
app/Services/[Module]/[Entity]/[Entity]Interface.php

# Read the model to check traits and relationships
app/Models/[Entity].php

# Read existing API resource to understand field mappings
app/Http/Resources/Api/[Module]/[Entity]/[Entity]Resource.php
```

### Step 2: Check Model Traits
```php
// Check which traits the model uses:
use HasUuids;           // UUID primary key
use AppAuditable;       // Audit fields (created_by, updated_by)
use AppHasImages;       // Image upload capability ⚠️ IMPORTANT
use SoftDeletes;        // Soft deletes (deleted_by field)
```

### Step 3: Create Resource File
```bash
php artisan make:filament-resource [Entity] --generate --primary
```

### Step 4: Implement This Pattern
Copy the template below and customize based on:
- Model fields (check migration file)
- Service validation rules (check FormRequest)
- API Resource structure (for consistency)
- Model traits (AppHasImages, AppAuditable, etc.)

---

## 📦 Complete Resource Template

### File Location
```
app/Filament/Resources/[Module]/[Entity]s/[Entity]Resource.php
```

### Basic Template (Without Images)

```php
<?php

namespace App\Filament\Resources\[Module]\[Entity]s;

use App\Filament\Resources\[Module]\[Entity]s\Pages\Manage[Entity]s;
use App\Models\[Entity];
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class [Entity]Resource extends Resource
{
    protected static ?string $model = [Entity]::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Outlined[IconName];

    protected static ?int $navigationSort = 1;

    // Navigation group - use __('label.settings') for Settings/Pengaturan
    public static function getNavigationGroup(): string
    {
        return __('label.[group]');
    }

    // Navigation label - use __('label.[entity]s') for plural form
    public static function getNavigationLabel(): string
    {
        return __('label.[entity]s');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Add form components here
                // See "Form Components" section below
            ])
            ->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Add infolist components here
                // See "Infolist Components" section below
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('[title_field]')
            ->defaultSort('[created_at]', 'desc')
            ->columns([
                // Add table columns here
                // See "Table Columns" section below
            ])
            ->filters([
                // Add filters here
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Manage[Entity]s::route('/'),
        ];
    }
}
```

### Template With Images (Using AppHasImages)

```php
<?php

namespace App\Filament\Resources\[Module]\[Entity]s;

use App\Filament\Resources\[Module]\[Entity]s\Pages\Manage[Entity]s;
use App\Models\[Entity];
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class [Entity]Resource extends Resource
{
    protected static ?string $model = [Entity]::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Outlined[IconName];

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('label.[group]');
    }

    public static function getNavigationLabel(): string
    {
        return __('label.[entity]s');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('[field_name]')
                    ->label(__('label.[field_name]'))
                    ->placeholder(__('label.[field_name]'))
                    ->required()
                    ->string()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label(__('label.description'))
                    ->placeholder(__('label.description'))
                    ->nullable()
                    ->string()
                    ->rows(4)
                    ->columnSpan(1),

                FileUpload::make('[image_field]_path')
                    ->label(__('label.[image]'))
                    ->disk(fn (): string => (new [Entity]())->getImageDiskConfig()['disk']->value)
                    ->directory(fn (): string => (new [Entity]())->getImageDiskConfig()['directory']->value)
                    ->image()
                    ->required()
                    ->imageEditor()
                    ->columnSpan(1),

                Toggle::make('is_active')
                    ->label(__('label.isActive'))
                    ->inline(false)
                    ->default(true)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('label.information'))
                    ->schema([
                        TextEntry::make('[title_field]')
                            ->label(__('label.[title_field]'))
                            ->size('lg')
                            ->weight('bold'),

                        TextEntry::make('description')
                            ->label(__('label.description'))
                            ->placeholder('-')
                            ->markdown(),

                        ImageEntry::make('[image_field]_path')
                            ->label(__('label.[image]'))
                            ->disk(fn ([Entity] $record): ?string =>
                                $record->{[image_field]_disk} ?? $record->getImageDiskConfig()['disk']->value
                            ),

                        IconEntry::make('is_active')
                            ->label(__('label.isActive'))
                            ->boolean(),
                    ])
                    ->columnSpan(2),

                Section::make(__('label.audit'))
                    ->schema([
                        TextEntry::make('creator.name')
                            ->label(__('label.createdBy'))
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label(__('label.createdAt'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updater.name')
                            ->label(__('label.updatedBy'))
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label(__('label.updatedAt'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columnSpan(1)
                    ->collapsible(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('[title_field]')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('[title_field]')
                    ->label(__('label.[title_field]'))
                    ->searchable()
                    ->sortable(),

                ImageColumn::make('[image_field]_path')
                    ->label(__('label.[image]'))
                    ->disk(fn ([Entity] $record): ?string =>
                        $record->{[image_field]_disk} ?? $record->getImageDiskConfig()['disk']->value
                    ),

                IconColumn::make('is_active')
                    ->label(__('label.isActive'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('label.createdAt'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('label.updatedAt'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Manage[Entity]s::route('/'),
        ];
    }
}
```

---

## 🧩 CreateAction and EditAction (With Image Upload)

### Pages/[Entity]s/Manage[Entity]s.php

```php
<?php

namespace App\Filament\Resources\[Module]\[Entity]s\Pages;

use App\Filament\Resources\[Module]\[Entity]s\[Entity]Resource;
use App\Models\[Entity];
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class Manage[Entity]s extends ManageRecords
{
    protected static string $resource = [Entity]Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): [Entity] {
                    // Extract image from data
                    $image = $data['[image_field]_path'];
                    unset($data['[image_field]_path']);

                    // Create model
                    $entity = new [Entity]();
                    $entity->fill($data);

                    // Get storage config from model (single source of truth)
                    $diskConfig = $entity->getImageDiskConfig();

                    // Filament already uploaded the file, just set the path & disk
                    $entity->{[image_field]_path} = $image;
                    $entity->{[image_field]_disk} = $diskConfig['disk']->value;

                    $entity->save();

                    return $entity;
                }),
        ];
    }
}
```

### Update EditAction in Resource

```php
// In [Entity]Resource::table()

EditAction::make()
    ->using(function ([Entity] $record, array $data): void {
        $image = $data['[image_field]_path'] ?? null;

        // Remove image_path from data to avoid overwriting
        unset($data['[image_field]_path']);

        // Update other fields
        $record->fill($data);

        // Update image if changed (new upload or different from current)
        if ($image !== null && $image !== $record->{[image_field]_path}) {
            // Get storage config from model (single source of truth)
            $diskConfig = $record->getImageDiskConfig();

            // Filament already uploaded the file, just set the path & disk
            $record->{[image_field]_path} = $image;
            $record->{[image_field]_disk} = $diskConfig['disk']->value;
        }

        $record->save();
    }),
```

---

## 📋 Form Components Reference

### Text Input
```php
TextInput::make('name')
    ->label(__('label.name'))
    ->placeholder(__('label.name'))
    ->required()                    // or ->nullable()
    ->string()                      // validation type
    ->maxLength(255),               // max length
```

### Textarea
```php
Textarea::make('description')
    ->label(__('label.description'))
    ->placeholder(__('label.description'))
    ->nullable()
    ->string()
    ->rows(4)
    ->columnSpan(1),                // or ->columnSpanFull()
```

### Number Input
```php
TextInput::make('order')
    ->label(__('label.order'))
    ->placeholder(__('label.order'))
    ->nullable()
    ->integer()                     // or ->numeric()
    ->minValue(1)
    ->default(fn () => [Entity]::max('order') + 1),
```

### Toggle/Boolean
```php
Toggle::make('is_active')
    ->label(__('label.isActive'))
    ->inline(false)                  // or ->inline(true)
    ->default(true)
    ->columnSpanFull(),
```

### Select/Enum
```php
use Filament\Forms\Components\Select;

Select::make('status')
    ->label(__('label.status'))
    ->options([
        'active' => __('label.active'),
        'inactive' => __('label.inactive'),
    ])
    ->default('active')
    ->required(),

// OR with enum
Select::make('status')
    ->label(__('label.status'))
    ->options(UserStatus::toArray()) // Assuming enum has toArray() method
    ->required(),
```

---

## 🖼️ FileUpload Component (Images)

### Basic Image Upload
```php
FileUpload::make('image_path')
    ->label(__('label.image'))
    ->disk(fn (): string => (new [Entity]())->getImageDiskConfig()['disk']->value)
    ->directory(fn (): string => (new [Entity]())->getImageDiskConfig()['directory']->value)
    ->image()
    ->required()                     // or ->nullable()
    ->imageEditor()
    ->columnSpan(1),
```

### Important Notes for Image Upload:
1. **Field name:** Must be `{field}_path` (e.g., `image_path`, `avatar_path`)
2. **Disk callback:** Use `getImageDiskConfig()['disk']->value` from model
3. **Directory callback:** Use `getImageDiskConfig()['directory']->value` from model
4. **Database stores:** `{field}_path` and `{field}_disk` (NOT `{field}_storage`!)
5. **Never use:** `{field}_url` in database (URL is generated dynamically)

---

## 📊 Infolist Components Reference

### Section Grouping
```php
Section::make(__('label.information'))
    ->schema([
        // Components here
    ])
    ->columnSpan(2),                // or ->columnSpanFull()

Section::make(__('label.audit'))
    ->schema([
        // Audit info here
    ])
    ->columnSpan(1)
    ->collapsible(),                // make it collapsible
```

### TextEntry
```php
TextEntry::make('name')
    ->label(__('label.name'))
    ->size('lg')                     // sm, md, lg, xl
    ->weight('bold')                // normal, medium, semibold, bold
    ->placeholder('-'),
```

### Markdown TextEntry
```php
TextEntry::make('description')
    ->label(__('label.description'))
    ->placeholder('-')
    ->markdown(),                   // renders markdown
```

### IconEntry (Boolean)
```php
IconEntry::make('is_active')
    ->label(__('label.isActive'))
    ->boolean(),
```

### ImageEntry
```php
ImageEntry::make('image_path')
    ->label(__('label.image'))
    ->disk(fn ([Entity] $record): ?string =>
        $record->image_disk ?? $record->getImageDiskConfig()['disk']->value
    ),
```

### Numeric Entry
```php
TextEntry::make('order')
    ->label(__('label.order'))
    ->numeric(),
```

### Date/Time Entry
```php
TextEntry::make('created_at')
    ->label(__('label.createdAt'))
    ->dateTime()
    ->placeholder('-'),
```

---

## 📑 Table Columns Reference

### TextColumn
```php
TextColumn::make('name')
    ->label(__('label.name'))
    ->searchable()                  // enable search
    ->sortable(),                   // enable sorting
```

### Numeric Column
```php
TextColumn::make('order')
    ->label(__('label.order'))
    ->numeric()
    ->sortable(),
```

### ImageColumn
```php
ImageColumn::make('image_path')
    ->label(__('label.image'))
    ->disk(fn ([Entity] $record): ?string =>
        $record->image_disk ?? $record->getImageDiskConfig()['disk']->value
    ),
```

### IconColumn (Boolean)
```php
IconColumn::make('is_active')
    ->label(__('label.isActive'))
    ->boolean(),
```

### Date/Time Column
```php
TextColumn::make('created_at')
    ->label(__('label.createdAt'))
    ->dateTime()
    ->sortable()
    ->toggleable(isToggledHiddenByDefault: true),
```

---

## 🎯 Audit Information Pattern

### Model Requirements
```php
use Daniardev\LaravelTsd\Traits\AppAuditable;

class [Entity] extends Model
{
    use AppAuditable;  // Adds: created_by, updated_by, creator(), updater()
}
```

### Infolist with Audit
```php
Section::make(__('label.audit'))
    ->schema([
        TextEntry::make('creator.name')
            ->label(__('label.createdBy'))
            ->placeholder('-'),
        TextEntry::make('created_at')
            ->label(__('label.createdAt'))
            ->dateTime()
            ->placeholder('-'),
        TextEntry::make('updater.name')
            ->label(__('label.updatedBy'))
            ->placeholder('-'),
        TextEntry::make('updated_at')
            ->label(__('label.updatedAt'))
            ->dateTime()
            ->placeholder('-'),
    ])
    ->columnSpan(1)
    ->collapsible(),
```

---

## 🔧 Advanced Features

### Reorderable Table
```php
$table = $table
    ->reorderable('[order_field]')  // Enable drag-and-drop reordering
    ->defaultSort('[order_field]', 'asc')
```

### Filters
```php
use Filament\Tables\Filters\SelectFilter;

->filters([
    SelectFilter::make('status')
        ->label(__('label.status'))
        ->options([
            'active' => __('label.active'),
            'inactive' => __('label.inactive'),
        ]),
])
```

### Relation Columns
```php
// For BelongsTo relationships
TextColumn::make('category.name')
    ->label(__('label.category'))
    ->searchable()
    ->sortable(),
```

---

## 🌐 Locale Setup

### Add Locale Keys

**lang/id/label.php**
```php
return [
    // ... existing labels

    // Entity labels
    '[entity]s' => '[Translated Name in Indonesian]',
    '[field_name]' => '[Translated Field]',

    // Common labels (if not exists)
    'information' => 'Informasi',
    'audit' => 'Audit',
    'createdBy' => 'Dibuat oleh',
    'createdAt' => 'Dibuat pada',
    'updatedBy' => 'Diperbarui oleh',
    'updatedAt' => 'Diperbarui pada',
    'isActive' => 'Status Aktif',
    'settings' => 'Pengaturan',
];
```

**lang/en/label.php**
```php
return [
    // ... existing labels

    // Entity labels
    '[entity]s' => '[English Name]',
    '[field_name]' => '[Field Name]',

    // Common labels (if not exists)
    'information' => 'Information',
    'audit' => 'Audit',
    'createdBy' => 'Created By',
    'createdAt' => 'Created At',
    'updatedBy' => 'Updated By',
    'updatedAt' => 'Updated At',
    'isActive' => 'Is Active',
    'settings' => 'Settings',
];
```

---

## 🔍 AI Assistant Checklist

When creating a Filament Resource, AI should:

### Before Coding
- [ ] Read the Service Interface to understand entity structure
- [ ] Read the Model to check traits (AppAuditable, AppHasImages, etc.)
- [ ] Read the migration to see all fields
- [ ] Read the API Resource to understand data structure
- [ ] Check if FormRequest exists (for validation reference)

### During Implementation
- [ ] Use `__('label.xxx')` for ALL labels (no hardcoded strings)
- [ ] Add locale keys to both `lang/id/label.php` and `lang/en/label.php`
- [ ] Set `getNavigationLabel()` to return __('label.[entity]s')
- [ ] Set `getNavigationGroup()` to return __('label.[group]')
- [ ] Use `Section::make()` for grouping in infolist
- [ ] Add audit section if model uses `AppAuditable`
- [ ] For images: Use `getImageDiskConfig()` callbacks from model
- [ ] For images: Store `{field}_path` and `{field}_disk` (NOT `{field}_storage`!)
- [ ] Use `disk()` callback with fallback for ImageColumn and ImageEntry
- [ ] Add placeholder attributes for all form inputs

### Form Components
- [ ] Required fields: `->required()` with proper type
- [ ] Optional fields: `->nullable()`
- [ ] Text inputs: Add `->placeholder()`
- [ ] Number inputs: Add `->minValue()` if applicable
- [ ] Select/Enum: Use options from Enum's `toArray()` method
- [ ] FileUpload: Set `->disk()` and `->directory()` callbacks from model

### Table Columns
- [ ] Primary field: `->recordTitleAttribute('[title_field]')`
- [ ] Sortable columns: Add `->sortable()`
- [ ] Searchable columns: Add `->searchable()`
- [ ] Image columns: Add `->disk()` callback with fallback
- [ ] Date columns: Add `->dateTime()` and `->toggleable()`
- [ ] Hidden by default: `isToggledHiddenByDefault: true`

### Testing
- [ ] Test create action (especially with image upload)
- [ ] Test edit action (especially with image replacement)
- [ ] Test delete action
- [ ] Verify images display correctly in table
- [ ] Verify locale switching works (ID ↔ EN)

---

## 📚 Related Documentation

- **AppHasImages Pattern:** `/docs/patterns/app-has-images.md`
- **AppAuditable Trait:** `/docs/ai/quick-reference.md#section-172-appauditable`
- **Service Layer:** `/docs/patterns/service-layer.md`
- **Model Rules:** `/docs/ai/quick-reference.md#section-4-model-rules`
- **Form Request:** `/docs/ai/templates.md#6-form-request-template`

---

## 💡 Best Practices

### DO ✅
1. **Always use locale** - All labels must use `__('label.xxx')`
2. **Match API structure** - Fields should match API Resource structure
3. **Group related fields** - Use Section components in infolist
4. **Fallback for disk** - Always use `$record->field_disk ?? $record->getImageDiskConfig()`
5. **Placeholder text** - Add placeholders for all form inputs
6. **Audit info** - Always include audit section if model uses AppAuditable
7. **Column span** - Use appropriate column spans for layout (1, 2, Full)

### DON'T ❌
1. **Don't hardcode labels** - Always use `__('label.xxx')`
2. **Don't use `{field}_storage`** - Use `{field}_disk` for AppHasImages
3. **Don't use `{field}_url`** - URLs are generated dynamically
4. **Don't skip locale keys** - Add to both ID and EN files
5. **Don't forget disk callback** - Always use callback for ImageColumn/ImageEntry disk
6. **Don't mix concerns** - Keep Resource simple, business logic in Service

---

**Version:** 1.0.0
**Last Updated:** 2026-03-20
**Package:** Daniardev\LaravelTsd\Patterns
**Based on:** BoardingResource implementation