<?php

namespace App\Services;

class QuestionGeneratorService
{
    public function __construct(
        private GroqService $groq
    ) {}

    public function generate(string $role, string $difficulty): array
{
    $topics = [
        'Frontend'  => ['React hooks', 'CSS layout', 'browser APIs', 'performance optimization', 'state management', 'accessibility', 'webpack/vite bundlers', 'frontend testing', 'TypeScript', 'web security'],
        'Backend'   => ['REST APIs', 'database design', 'authentication', 'caching', 'message queues', 'ORM', 'security', 'scalability', 'microservices', 'background jobs'],
        'Fullstack' => ['API design', 'database modeling', 'deployment', 'CI/CD', 'session management', 'WebSockets', 'SSR vs CSR', 'monorepo', 'rate limiting', 'file uploads'],
        'DevOps'    => ['Docker', 'Kubernetes', 'CI/CD pipelines', 'monitoring and logging', 'Infrastructure as Code', 'networking', 'cloud services', 'security', 'scaling strategies', 'disaster recovery'],
    ];

    // Pick 5 random topics to force variety each session
    $roleTopics = $topics[$role] ?? $topics['Fullstack'];
    shuffle($roleTopics);
    $selectedTopics = array_slice($roleTopics, 0, 5);
    $topicList = implode(', ', $selectedTopics);

    // Random seed makes Groq generate different questions each time
    $seed = rand(1000, 9999);

    $prompt = <<<PROMPT
You are a senior technical interviewer preparing interview questions.

Generate exactly 5 unique technical interview questions for a {$role} developer position.
Difficulty level: {$difficulty}
Session seed: {$seed}

REQUIRED TOPICS — each question must cover one of these (one topic per question):
{$topicList}

RULES:
- Each question covers a DIFFERENT topic from the list above
- Vary question types: mix definitional ("what is"), scenario-based ("you are tasked with"), and implementation ("write/design/implement")
- At least one question must ask the candidate to write code, pseudocode, or design a system
- No trick questions or obscure trivia
- Questions must be practical and relevant to real {$role} work
- For easy: focus on fundamentals and definitions
- For medium: focus on concepts, how things work, and simple implementations
- For hard: focus on architecture, trade-offs, system design, and deep understanding
- Make questions specific — not generic like "explain REST" but "design a REST endpoint that handles X"

Return ONLY a valid JSON array of exactly 5 strings, no extra text:
["question 1", "question 2", "question 3", "question 4", "question 5"]
PROMPT;

    $raw  = $this->groq->generate($prompt);
    $text = trim($raw['response'] ?? '');

    // Strip markdown code blocks if present
    $text = preg_replace('/```json|```/', '', $text);
    $text = trim($text);

    $questions = json_decode($text, true);

    if (!is_array($questions) || count($questions) < 3) {
        throw new \RuntimeException('Failed to generate valid questions: ' . $text);
    }

    return array_values(array_slice($questions, 0, 5));
}
}