<?php

declare(strict_types=1);

/**
 * Example: validate JSON data against a JSON Schema.
 *
 * Run from the json-schema project root:
 *   php examples/validate_data.php
 */

require __DIR__ . '/../vendor/autoload.php';

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

$validator = new Validator();

// --- Basic object validation ---
$data = (object)[
    'name'  => 'Alice',
    'age'   => 30,
    'email' => 'alice@example.com',
];

$schema = (object)[
    'type'       => 'object',
    'required'   => ['name', 'age'],
    'properties' => (object)[
        'name'  => (object)['type' => 'string', 'minLength' => 1],
        'age'   => (object)['type' => 'integer', 'minimum' => 0],
        'email' => (object)['type' => 'string', 'format' => 'email'],
    ],
];

$validator->validate($data, $schema);

if ($validator->isValid()) {
    echo "Data is valid.\n";
} else {
    echo "Validation errors:\n";
    foreach ($validator->getErrors() as $error) {
        echo "  [{$error['property']}] {$error['message']}\n";
    }
}

// --- Coercive type checking (string "30" is coerced to integer 30) ---
$data2 = (object)['name' => 'Bob', 'age' => '25'];
$validator->validate($data2, $schema, Constraint::CHECK_MODE_COERCE_TYPES);

echo "\nWith coercion — valid: " . ($validator->isValid() ? 'yes' : 'no') . "\n";
echo "Coerced age type: " . gettype($data2->age) . "\n";

// --- Array validation ---
$list = [1, 2, 3, 4, 5];
$arraySchema = (object)[
    'type'        => 'array',
    'items'       => (object)['type' => 'integer'],
    'minItems'    => 1,
    'uniqueItems' => true,
];

$validator->validate($list, $arraySchema);
echo "\nArray valid: " . ($validator->isValid() ? 'yes' : 'no') . "\n";
