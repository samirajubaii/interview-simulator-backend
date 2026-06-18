<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');

        $bank = [
            'Frontend' => [
                'easy' => [
                    'What is HTML used for in a web application?',
                    'What is the difference between HTML and CSS?',
                    'What does CSS stand for and what is it used for?',
                    'What is a responsive layout?',
                ],
                'medium' => [
                    'Explain the difference between useState and useEffect in React.',
                    'What is the Virtual DOM and why is it useful?',
                    'What is component state in React?',
                    'Explain props vs state in React.',
                    'What is the purpose of keys in a React list?',
                ],
                'hard' => [
                    'How would you optimize performance in a large React application?',
                    'Explain when you would use useMemo and useCallback.',
                    'What are common causes of unnecessary re-renders in React?',
                    'How does React 18 concurrent rendering help user experience?',
                ],
            ],
            'Backend' => [
                'easy' => [
                    'What is an API?',
                    'What is the difference between GET and POST?',
                    'What is JSON and why is it common in APIs?',
                    'What is a database migration?',
                ],
                'medium' => [
                    'What is the difference between REST and GraphQL?',
                    'Explain MVC in backend development.',
                    'What is middleware in a web framework?',
                    'What is the difference between authentication and authorization?',
                    'What are database indexes and why use them?',
                ],
                'hard' => [
                    'How do you handle authentication in a web application?',
                    'Explain database transactions and when you need them.',
                    'How would you design rate limiting for a public API?',
                    'What strategies help prevent N+1 query problems?',
                ],
            ],
            'Full Stack' => [
                'easy' => [
                    'What is the difference between frontend and backend?',
                    'What is a full-stack application?',
                    'What is an environment variable?',
                    'What is version control and why use Git?',
                ],
                'medium' => [
                    'How does the browser communicate with the server?',
                    'What is CORS and why does it exist?',
                    'Explain session-based login at a high level.',
                    'What is the purpose of an ORM like Eloquent?',
                ],
                'hard' => [
                    'How would you design a scalable web application?',
                    'How do you structure a monolith vs microservices trade-off?',
                    'Explain caching layers from browser to database.',
                    'How would you debug a slow end-to-end request?',
                ],
            ],
            'Data Engineer' => [
                'easy' => [
                    'What is a database table?',
                    'What is the difference between a row and a column?',
                    'What is CSV used for?',
                    'What does ETL stand for?',
                ],
                'medium' => [
                    'Explain ETL pipelines and their importance.',
                    'What is the difference between OLTP and OLAP?',
                    'What is data normalization?',
                    'Explain batch vs stream processing.',
                ],
                'hard' => [
                    'How would you design a data warehouse schema?',
                    'What is data quality and how do you monitor it?',
                    'Explain idempotent data pipelines.',
                    'How do you handle late-arriving events in streaming data?',
                ],
            ],
            'Mobile' => [
                'easy' => [
                    'What is a mobile native app?',
                    'What is an app store?',
                    'What is a push notification?',
                    'What is offline-first design?',
                ],
                'medium' => [
                    'What is the difference between native and cross-platform apps?',
                    'Explain React Native at a high level.',
                    'What are common mobile performance bottlenecks?',
                    'How do you handle different screen sizes?',
                ],
                'hard' => [
                    'How would you design secure local storage on mobile?',
                    'Explain mobile app release strategies (staging, beta, prod).',
                    'How do you reduce battery drain from background tasks?',
                    'Compare Flutter vs React Native trade-offs.',
                ],
            ],
            'DevOps' => [
                'easy' => [
                    'What is deployment?',
                    'What is a server?',
                    'What is Docker in simple terms?',
                    'What is a production environment?',
                ],
                'medium' => [
                    'What is CI/CD and why is it important?',
                    'Explain the difference between continuous delivery and deployment.',
                    'What is infrastructure as code?',
                    'What is a container vs a virtual machine?',
                ],
                'hard' => [
                    'How would you design a zero-downtime deployment?',
                    'Explain blue-green vs canary releases.',
                    'How do you monitor and alert on production systems?',
                    'What is your approach to secrets management in CI/CD?',
                ],
            ],
        ];

        foreach ($bank as $categoryName => $byDifficulty) {
            $categoryId = $categories[$categoryName] ?? null;

            if (!$categoryId) {
                continue;
            }

            foreach ($byDifficulty as $difficulty => $questions) {
                foreach ($questions as $text) {
                    Question::firstOrCreate(
                        [
                            'question' => $text,
                            'category_id' => $categoryId,
                        ],
                        [
                            'difficulty' => $difficulty,
                            'keywords' => null,
                        ]
                    );
                }
            }
        }
    }
}
