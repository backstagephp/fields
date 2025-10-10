# Visibility Rules Documentation

The Visibility Rules feature allows you to control when fields are shown or hidden based on dynamic conditions. This creates intelligent, adaptive forms that respond to user input and record properties.

## Overview

Visibility rules work by evaluating conditions in real-time as users interact with your forms. When conditions are met, fields become visible; when they're not met, fields are hidden. This creates a more intuitive user experience by only showing relevant fields.

## Types of Conditions

### 1. Field-Based Conditions

Show/hide fields based on other field values in the same form.

**Use Cases:**
- Show additional fields when a checkbox is checked
- Display different options based on a select field value
- Show/hide fields based on text input content

**Example:**
```json
{
  "source": "field",
  "property": "field_ulid_here",
  "operator": "equals",
  "value": "yes"
}
```

### 2. Model Attribute Conditions

Show/hide fields based on properties of the current record.

**Use Cases:**
- Show fields only for specific record types or categories
- Hide fields on certain records (like default/welcome pages)
- Display fields based on record status, role, or other properties

**Example:**
```json
{
  "source": "model_attribute",
  "model": "App\\Models\\Product",
  "property": "category",
  "operator": "equals",
  "value": "electronics"
}
```

## Configuration

### Setting Up Model Attributes

To use model attribute conditions, you need to configure which models are available for visibility rules.

1. **Add models to config:**
```php
// config/backstage/fields.php
return [
    'visibility_models' => [
        \App\Models\Product::class,
        \App\Models\User::class,
        \App\Models\Category::class,
        \App\Models\Post::class,
    ],
];
```

2. **Model attribute discovery:**
The system automatically discovers available attributes by:
- Reading the database schema for the model's table
- Falling back to the model's fillable attributes
- Providing common attribute names as a final fallback

### Available Attributes

The system automatically excludes certain attributes that aren't useful for visibility rules:
- `id`, `created_at`, `updated_at`, `deleted_at`

Common attributes that are typically available:
- `name`, `slug`, `title`, `description`, `content`
- `category`, `type`, `status`, `role`, `active`
- `price`, `quantity`, `featured`, `published`
- `email`, `phone`, `address`, `city`, `country`

## Operators

### Equality Operators
- **`equals`**: Field value equals the specified value
- **`not_equals`**: Field value does not equal the specified value

### Text Operators
- **`contains`**: Field value contains the specified text
- **`not_contains`**: Field value does not contain the specified text
- **`starts_with`**: Field value starts with the specified text
- **`ends_with`**: Field value ends with the specified text

### Empty Check Operators
- **`is_empty`**: Field value is empty (null, empty string, or empty array)
- **`is_not_empty`**: Field value is not empty

### Numeric Operators
- **`greater_than`**: Field value is greater than the specified number
- **`less_than`**: Field value is less than the specified number
- **`greater_than_or_equal`**: Field value is greater than or equal to the specified number
- **`less_than_or_equal`**: Field value is less than or equal to the specified number

### List Operators
- **`in`**: Field value is one of the specified values (comma-separated)
- **`not_in`**: Field value is not one of the specified values (comma-separated)

## Logical Operators

### AND Logic
All conditions must be met for the field to be visible.

```json
{
  "logic": "AND",
  "conditions": [
    {
      "source": "model_attribute",
      "model": "App\\Models\\Product",
      "property": "category",
      "operator": "equals",
      "value": "electronics"
    },
    {
      "source": "model_attribute",
      "model": "App\\Models\\Product",
      "property": "status",
      "operator": "equals",
      "value": "active"
    }
  ]
}
```

### OR Logic
Any condition can be met for the field to be visible.

```json
{
  "logic": "OR",
  "conditions": [
    {
      "source": "model_attribute",
      "model": "App\\Models\\User",
      "property": "role",
      "operator": "equals",
      "value": "admin"
    },
    {
      "source": "field",
      "property": "field_ulid_here",
      "operator": "equals",
      "value": "premium"
    }
  ]
}
```

## Common Use Cases

### 1. Category-Based Fields

Show specific fields only for certain categories:

```json
{
  "logic": "AND",
  "conditions": [
    {
      "source": "model_attribute",
      "model": "App\\Models\\Product",
      "property": "category",
      "operator": "equals",
      "value": "electronics"
    }
  ]
}
```

### 2. Hide Fields on Specific Records

Hide fields on default or welcome records:

```json
{
  "logic": "AND",
  "conditions": [
    {
      "source": "model_attribute",
      "model": "App\\Models\\Post",
      "property": "slug",
      "operator": "not_equals",
      "value": "welcome"
    }
  ]
}
```

### 3. Status-Based Visibility

Show fields based on record status:

```json
{
  "logic": "AND",
  "conditions": [
    {
      "source": "model_attribute",
      "model": "App\\Models\\Product",
      "property": "status",
      "operator": "equals",
      "value": "active"
    }
  ]
}
```

### 4. Multi-Condition Logic

Combine multiple conditions for complex visibility rules:

```json
{
  "logic": "AND",
  "conditions": [
    {
      "source": "model_attribute",
      "model": "App\\Models\\Product",
      "property": "category",
      "operator": "in",
      "value": "electronics,computers"
    },
    {
      "source": "model_attribute",
      "model": "App\\Models\\Product",
      "property": "featured",
      "operator": "equals",
      "value": "1"
    }
  ]
}
```

### 5. Field Dependencies

Show fields based on other field values:

```json
{
  "logic": "AND",
  "conditions": [
    {
      "source": "field",
      "property": "field_ulid_here",
      "operator": "equals",
      "value": "premium"
    }
  ]
}
```

## Troubleshooting

### Fields Not Showing/Hiding as Expected

1. **Check the condition syntax**: Ensure JSON is valid and all required fields are present
2. **Verify model configuration**: Make sure the model is added to `visibility_models` config
3. **Check attribute names**: Ensure the property name matches the actual model attribute
4. **Test with different values**: Try different operators and values to isolate the issue

### Common Issues

1. **Model not found**: Add the model to the `visibility_models` config array
2. **Attribute not available**: Check if the attribute exists in the database schema
3. **Case sensitivity**: Ensure string comparisons match the exact case
4. **Data type mismatches**: Ensure numeric operators are used with numeric values

### Debug Tips

1. **Use simple conditions first**: Start with basic `equals` conditions
2. **Test one condition at a time**: Add complexity gradually
3. **Check the actual data**: Verify what values are actually stored in your models

## Integration with Validation Rules

Visibility rules work seamlessly with validation rules:

- **Hidden fields are not validated**: Fields that are hidden due to visibility rules are automatically excluded from validation
- **Conditional validation**: You can combine visibility rules with conditional validation rules
- **Dynamic requirements**: Fields can be required only when they're visible

This creates a cohesive system where both visibility and validation adapt to your data and user interactions.
