<?php

return [


    "worksheet" => [
        "title" => "Guess Who",
        "description" => "A worksheet to practice yes/no questions in different verb tenses using the 'Guess Who' game format. This worksheet includes a set of characters and a series of questions that students can ask to guess the character based on the answers provided.",
        "instructions" => "Students ask questions to guess the character based on the clues and answers provided.",
        "prompt_instructions" => "Generate a set of four clues for the game.  Some clues can be repeated across different characters, but each character must have at least one unique clue. The clues should be designed to help students practice forming questions. Clues should be short and simple. Example: [Clue] - Will travel to...,[Answer] - France. Clues and answers should match gramatically. The example question must be taken from a real example of a character.",
    ],

    "lesson" => [
        "language" => "English",
        "cefr" => "A1",
        "topic" => "Present Simple",
        "grammar" => [
            "present simple",
            "routines"
        ]
    ],

    "teacher" => [
        "instructions" => "Use vocabulary for present habits or routines like always, usually, often, sometimes, never"
    ],

    "template" => [
        "id" => "guess-who",
        "requirements" => [
            "characters" => 9,
            "clues" => 4,
            "answers_per_character" => 4,
            "max_clue_length" => 25,
            "max_answer_length" => 25
        ]
    ],

    "output_schema" => [

        "example_question" => "string",
        "positive_answer" => "string",
        "negative_answer" => "string",

        "clues" => [
            [
                "icon" => "string",
                "clue" => "string",
            ]
        ],

        "characters" => [
            [
                "answers" => [
                    "answer1", "answer2", "answer3", "answer4"
                ]
            ]
        ]
    ]

];
