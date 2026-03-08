<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run()
    {
        $quizzes = Quiz::all();

        foreach ($quizzes as $quiz) {
            $questions = $this->getQuestionsForCategory($quiz->category->name, $quiz->difficulty);
            
            foreach ($questions as $index => $questionData) {
                Question::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $questionData['text'],
                    'options' => $questionData['options'],
                    'correct_answer' => $questionData['correct_answer'],
                    'explanation' => $questionData['explanation'] ?? null,
                    'difficulty' => $questionData['difficulty'] ?? $this->mapDifficulty($quiz->difficulty),
                    'points' => $questionData['points'] ?? $quiz->points_per_question,
                    'order' => $index + 1,
                ]);
            }

            // Update total questions count for the quiz
            $quiz->updateTotalQuestions();
        }

        $this->command->info('Questions seeded successfully!');
    }

    private function getQuestionsForCategory($categoryName, $quizDifficulty)
    {
        $questions = [];

        switch ($categoryName) {
            case 'General Knowledge':
                $questions = $this->getGeneralKnowledgeQuestions();
                break;
            case 'Science':
                $questions = $this->getScienceQuestions();
                break;
            case 'Mathematics':
                $questions = $this->getMathematicsQuestions();
                break;
            case 'History':
                $questions = $this->getHistoryQuestions();
                break;
            case 'Geography':
                $questions = $this->getGeographyQuestions();
                break;
            case 'Literature':
                $questions = $this->getLiteratureQuestions();
                break;
            case 'Sports':
                $questions = $this->getSportsQuestions();
                break;
            case 'Technology':
                $questions = $this->getTechnologyQuestions();
                break;
            default:
                $questions = $this->getGeneralKnowledgeQuestions();
        }

        // Adjust difficulty based on quiz difficulty
        foreach ($questions as &$question) {
            $question['difficulty'] = $this->mapDifficulty($quizDifficulty);
        }

        return $questions;
    }

    private function getGeneralKnowledgeQuestions()
    {
        return [
            [
                'text' => 'What is the capital of France?',
                'options' => ['A' => 'London', 'B' => 'Berlin', 'C' => 'Paris', 'D' => 'Madrid'],
                'correct_answer' => 'C',
                'explanation' => 'Paris is the capital and most populous city of France.',
            ],
            [
                'text' => 'Which planet is known as the Red Planet?',
                'options' => ['A' => 'Venus', 'B' => 'Mars', 'C' => 'Jupiter', 'D' => 'Saturn'],
                'correct_answer' => 'B',
                'explanation' => 'Mars appears reddish due to iron oxide (rust) on its surface.',
            ],
            [
                'text' => 'Who painted the Mona Lisa?',
                'options' => ['A' => 'Vincent van Gogh', 'B' => 'Pablo Picasso', 'C' => 'Leonardo da Vinci', 'D' => 'Michelangelo'],
                'correct_answer' => 'C',
                'explanation' => 'Leonardo da Vinci painted the Mona Lisa in the early 16th century.',
            ],
            [
                'text' => 'What is the largest ocean on Earth?',
                'options' => ['A' => 'Atlantic Ocean', 'B' => 'Indian Ocean', 'C' => 'Arctic Ocean', 'D' => 'Pacific Ocean'],
                'correct_answer' => 'D',
                'explanation' => 'The Pacific Ocean is the largest and deepest ocean on Earth.',
            ],
            [
                'text' => 'In which year did World War II end?',
                'options' => ['A' => '1943', 'B' => '1944', 'C' => '1945', 'D' => '1946'],
                'correct_answer' => 'C',
                'explanation' => 'World War II ended in 1945 with the surrender of Germany and Japan.',
            ],
        ];
    }

    private function getScienceQuestions()
    {
        return [
            [
                'text' => 'What is the chemical symbol for gold?',
                'options' => ['A' => 'Go', 'B' => 'Gd', 'C' => 'Au', 'D' => 'Ag'],
                'correct_answer' => 'C',
                'explanation' => 'Au comes from the Latin word for gold, "aurum".',
            ],
            [
                'text' => 'What is the hardest natural substance on Earth?',
                'options' => ['A' => 'Platinum', 'B' => 'Diamond', 'C' => 'Titanium', 'D' => 'Steel'],
                'correct_answer' => 'B',
                'explanation' => 'Diamond is the hardest naturally occurring substance.',
            ],
            [
                'text' => 'What is the speed of light in vacuum?',
                'options' => ['A' => '299,792 km/s', 'B' => '199,792 km/s', 'C' => '399,792 km/s', 'D' => '499,792 km/s'],
                'correct_answer' => 'A',
                'explanation' => 'Light travels at approximately 299,792 kilometers per second in vacuum.',
            ],
            [
                'text' => 'What is the most abundant gas in Earth\'s atmosphere?',
                'options' => ['A' => 'Oxygen', 'B' => 'Carbon Dioxide', 'C' => 'Nitrogen', 'D' => 'Hydrogen'],
                'correct_answer' => 'C',
                'explanation' => 'Nitrogen makes up about 78% of Earth\'s atmosphere.',
            ],
            [
                'text' => 'What is the smallest unit of matter?',
                'options' => ['A' => 'Molecule', 'B' => 'Atom', 'C' => 'Electron', 'D' => 'Quark'],
                'correct_answer' => 'D',
                'explanation' => 'Quarks are elementary particles and fundamental constituents of matter.',
            ],
        ];
    }

    private function getMathematicsQuestions()
    {
        return [
            [
                'text' => 'What is the value of π (pi) to two decimal places?',
                'options' => ['A' => '3.14', 'B' => '3.16', 'C' => '3.12', 'D' => '3.18'],
                'correct_answer' => 'A',
                'explanation' => 'Pi (π) is approximately 3.14159, which rounds to 3.14.',
            ],
            [
                'text' => 'What is the square root of 144?',
                'options' => ['A' => '10', 'B' => '11', 'C' => '12', 'D' => '13'],
                'correct_answer' => 'C',
                'explanation' => '12 × 12 = 144, so the square root of 144 is 12.',
            ],
            [
                'text' => 'What is 7 × 8?',
                'options' => ['A' => '48', 'B' => '56', 'C' => '64', 'D' => '72'],
                'correct_answer' => 'B',
                'explanation' => '7 × 8 = 56.',
            ],
            [
                'text' => 'What is the formula for the area of a circle?',
                'options' => ['A' => '2πr', 'B' => 'πr²', 'C' => 'πd', 'D' => '4πr²'],
                'correct_answer' => 'B',
                'explanation' => 'The area of a circle is π times the radius squared (πr²).',
            ],
            [
                'text' => 'What is 15% of 200?',
                'options' => ['A' => '20', 'B' => '25', 'C' => '30', 'D' => '35'],
                'correct_answer' => 'C',
                'explanation' => '15% of 200 is 30 (200 × 0.15 = 30).',
            ],
        ];
    }

    private function getHistoryQuestions()
    {
        return [
            [
                'text' => 'Who was the first President of the United States?',
                'options' => ['A' => 'Thomas Jefferson', 'B' => 'Abraham Lincoln', 'C' => 'George Washington', 'D' => 'John Adams'],
                'correct_answer' => 'C',
                'explanation' => 'George Washington served as the first U.S. president from 1789 to 1797.',
            ],
            [
                'text' => 'In which year did the Berlin Wall fall?',
                'options' => ['A' => '1987', 'B' => '1988', 'C' => '1989', 'D' => '1990'],
                'correct_answer' => 'C',
                'explanation' => 'The Berlin Wall fell on November 9, 1989, marking the end of the Cold War.',
            ],
            [
                'text' => 'Who was the first man to walk on the moon?',
                'options' => ['A' => 'Buzz Aldrin', 'B' => 'Neil Armstrong', 'C' => 'Yuri Gagarin', 'D' => 'Michael Collins'],
                'correct_answer' => 'B',
                'explanation' => 'Neil Armstrong walked on the moon on July 20, 1969, during the Apollo 11 mission.',
            ],
            [
                'text' => 'Which ancient civilization built the Machu Picchu?',
                'options' => ['A' => 'Aztecs', 'B' => 'Mayans', 'C' => 'Incas', 'D' => 'Olmecs'],
                'correct_answer' => 'C',
                'explanation' => 'Machu Picchu was built by the Inca civilization in the 15th century.',
            ],
            [
                'text' => 'Who was known as the "Maid of Orleans"?',
                'options' => ['A' => 'Marie Antoinette', 'B' => 'Joan of Arc', 'C' => 'Queen Elizabeth I', 'D' => 'Catherine the Great'],
                'correct_answer' => 'B',
                'explanation' => 'Joan of Arc, a peasant girl, led the French army during the Hundred Years\' War.',
            ],
        ];
    }

    private function getGeographyQuestions()
    {
        return [
            [
                'text' => 'What is the longest river in the world?',
                'options' => ['A' => 'Amazon', 'B' => 'Nile', 'C' => 'Yangtze', 'D' => 'Mississippi'],
                'correct_answer' => 'B',
                'explanation' => 'The Nile River is approximately 6,650 km long, making it the longest river.',
            ],
            [
                'text' => 'Which country has the most natural lakes?',
                'options' => ['A' => 'USA', 'B' => 'Russia', 'C' => 'Canada', 'D' => 'China'],
                'correct_answer' => 'C',
                'explanation' => 'Canada has more lake area than any other country, with over 2 million lakes.',
            ],
            [
                'text' => 'What is the smallest country in the world?',
                'options' => ['A' => 'Monaco', 'B' => 'San Marino', 'C' => 'Vatican City', 'D' => 'Liechtenstein'],
                'correct_answer' => 'C',
                'explanation' => 'Vatican City is the smallest country, with an area of just 0.44 square kilometers.',
            ],
            [
                'text' => 'Which desert is the largest in the world?',
                'options' => ['A' => 'Sahara', 'B' => 'Arabian', 'C' => 'Gobi', 'D' => 'Kalahari'],
                'correct_answer' => 'A',
                'explanation' => 'The Sahara Desert is the largest hot desert, covering 9.2 million square kilometers.',
            ],
            [
                'text' => 'What is the capital of Japan?',
                'options' => ['A' => 'Seoul', 'B' => 'Beijing', 'C' => 'Bangkok', 'D' => 'Tokyo'],
                'correct_answer' => 'D',
                'explanation' => 'Tokyo is the capital and largest city of Japan.',
            ],
        ];
    }

    private function getLiteratureQuestions()
    {
        return [
            [
                'text' => 'Who wrote "Romeo and Juliet"?',
                'options' => ['A' => 'Charles Dickens', 'B' => 'William Shakespeare', 'C' => 'Jane Austen', 'D' => 'Mark Twain'],
                'correct_answer' => 'B',
                'explanation' => 'William Shakespeare wrote Romeo and Juliet in the late 16th century.',
            ],
            [
                'text' => 'Which novel begins with "Call me Ishmael"?',
                'options' => ['A' => 'Moby Dick', 'B' => 'The Old Man and the Sea', 'C' => 'Treasure Island', 'D' => '20,000 Leagues Under the Sea'],
                'correct_answer' => 'A',
                'explanation' => 'Moby-Dick by Herman Melville famously opens with this line.',
            ],
            [
                'text' => 'Who wrote "Pride and Prejudice"?',
                'options' => ['A' => 'Emily Brontë', 'B' => 'Charles Dickens', 'C' => 'Jane Austen', 'D' => 'George Eliot'],
                'correct_answer' => 'C',
                'explanation' => 'Jane Austen wrote Pride and Prejudice, published in 1813.',
            ],
            [
                'text' => 'In which book would you find the character "Sherlock Holmes"?',
                'options' => ['A' => 'The Hound of the Baskervilles', 'B' => 'Dracula', 'C' => 'Frankenstein', 'D' => 'The Picture of Dorian Gray'],
                'correct_answer' => 'A',
                'explanation' => 'Sherlock Holmes appears in The Hound of the Baskervilles and many other stories by Arthur Conan Doyle.',
            ],
            [
                'text' => 'Who wrote "1984"?',
                'options' => ['A' => 'Aldous Huxley', 'B' => 'George Orwell', 'C' => 'Ray Bradbury', 'D' => 'H.G. Wells'],
                'correct_answer' => 'B',
                'explanation' => 'George Orwell wrote 1984, a dystopian novel published in 1949.',
            ],
        ];
    }

    private function getSportsQuestions()
    {
        return [
            [
                'text' => 'How many players are on a soccer team on the field?',
                'options' => ['A' => '9', 'B' => '10', 'C' => '11', 'D' => '12'],
                'correct_answer' => 'C',
                'explanation' => 'A soccer team has 11 players on the field, including the goalkeeper.',
            ],
            [
                'text' => 'Which country won the FIFA World Cup in 2018?',
                'options' => ['A' => 'Germany', 'B' => 'Brazil', 'C' => 'Spain', 'D' => 'France'],
                'correct_answer' => 'D',
                'explanation' => 'France won the 2018 FIFA World Cup, defeating Croatia 4-2 in the final.',
            ],
            [
                'text' => 'What is the diameter of a basketball hoop in inches?',
                'options' => ['A' => '16', 'B' => '18', 'C' => '20', 'D' => '22'],
                'correct_answer' => 'B',
                'explanation' => 'A standard basketball hoop has a diameter of 18 inches.',
            ],
            [
                'text' => 'In which sport would you perform a "slam dunk"?',
                'options' => ['A' => 'Volleyball', 'B' => 'Basketball', 'C' => 'Tennis', 'D' => 'Handball'],
                'correct_answer' => 'B',
                'explanation' => 'A slam dunk is a type of basketball shot where a player jumps and puts the ball directly through the hoop.',
            ],
            [
                'text' => 'How many Olympic rings are there?',
                'options' => ['A' => '4', 'B' => '5', 'C' => '6', 'D' => '7'],
                'correct_answer' => 'B',
                'explanation' => 'There are 5 Olympic rings, representing the five continents: Africa, Americas, Asia, Europe, and Oceania.',
            ],
        ];
    }

    private function getTechnologyQuestions()
    {
        return [
            [
                'text' => 'Who founded Microsoft?',
                'options' => ['A' => 'Steve Jobs', 'B' => 'Bill Gates', 'C' => 'Mark Zuckerberg', 'D' => 'Jeff Bezos'],
                'correct_answer' => 'B',
                'explanation' => 'Bill Gates and Paul Allen founded Microsoft in 1975.',
            ],
            [
                'text' => 'What does CPU stand for?',
                'options' => ['A' => 'Central Processing Unit', 'B' => 'Computer Personal Unit', 'C' => 'Central Program Utility', 'D' => 'Core Processing Unit'],
                'correct_answer' => 'A',
                'explanation' => 'CPU stands for Central Processing Unit, the brain of the computer.',
            ],
            [
                'text' => 'What year was the first iPhone released?',
                'options' => ['A' => '2005', 'B' => '2006', 'C' => '2007', 'D' => '2008'],
                'correct_answer' => 'C',
                'explanation' => 'The first iPhone was released by Apple on June 29, 2007.',
            ],
            [
                'text' => 'What does HTML stand for?',
                'options' => ['A' => 'Hyper Text Markup Language', 'B' => 'High Tech Modern Language', 'C' => 'Hyper Transfer Markup Language', 'D' => 'Home Tool Markup Language'],
                'correct_answer' => 'A',
                'explanation' => 'HTML stands for Hyper Text Markup Language, the standard markup language for web pages.',
            ],
            [
                'text' => 'Which company developed the Android operating system?',
                'options' => ['A' => 'Apple', 'B' => 'Microsoft', 'C' => 'Google', 'D' => 'Samsung'],
                'correct_answer' => 'C',
                'explanation' => 'Google developed Android, which was first released in 2008.',
            ],
        ];
    }

    private function mapDifficulty($quizDifficulty)
    {
        $map = [
            'beginner' => 'easy',
            'intermediate' => 'medium',
            'advanced' => 'hard',
            'expert' => 'hard',
        ];

        return $map[$quizDifficulty] ?? 'medium';
    }
}