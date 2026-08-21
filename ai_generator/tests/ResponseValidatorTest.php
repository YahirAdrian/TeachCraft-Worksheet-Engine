<?php

require __DIR__ . '/../vendor/autoload.php';

use AIGenerator\ResponseValidator;

$validator = new ResponseValidator();

function expectPass(ResponseValidator $validator, array $response, array $schema, string $label): void
{
    try {
        $validator->validate($response, $schema);
    } catch (Exception $e) {
        fwrite(STDERR, "{$label}\n  Expected to pass but failed: {$e->getMessage()}" . PHP_EOL);
        exit(1);
    }
}

function expectFail(ResponseValidator $validator, array $response, array $schema, string $label): void
{
    try {
        $validator->validate($response, $schema);
    } catch (Exception $e) {
        return;
    }

    fwrite(STDERR, "{$label}\n  Expected to fail but passed." . PHP_EOL);
    exit(1);
}

$schema = [
    'example_question' => 'string',
    'clues' => [
        [
            'icon' => 'string',
            'label' => 'string',
        ],
    ],
    'characters' => [
        [
            'answers' => ['string'],
        ],
    ],
];

$valid = [
    'example_question' => 'Does Mia drink coffee?',
    'clues' => [
        ['icon' => 'coffee', 'label' => 'Drinks in the morning'],
    ],
    'characters' => [
        ['answers' => ['coffee', 'by bus']],
    ],
];

expectPass($validator, $valid, $schema, 'Valid response');

$missingKey = $valid;
unset($missingKey['clues']);
expectFail($validator, $missingKey, $schema, 'Missing required key');

$wrongTypes = $valid;
$wrongTypes['example_question'] = 123;
expectFail($validator, $wrongTypes, $schema, 'Wrong scalar type');

$wrongListType = $valid;
$wrongListType['characters'][0]['answers'] = ['ok', 42];
expectFail($validator, $wrongListType, $schema, 'Non-string item in list');

$wrongObjectItem = $valid;
$wrongObjectItem['clues'][0] = 'not-an-object';
expectFail($validator, $wrongObjectItem, $schema, 'Non-object item in object list');

$emptyList = [
    'example_question' => 'Does Mia drink coffee?',
    'clues' => [
        ['icon' => 'coffee', 'label' => 'Drinks in the morning'],
    ],
    'characters' => [],
];
expectPass($validator, $emptyList, $schema, 'Empty collection is valid');

fwrite(STDOUT, "ResponseValidator test passed." . PHP_EOL);
