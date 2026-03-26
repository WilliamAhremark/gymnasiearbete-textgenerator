<?php
/**
 * ai-fallback.php — Local pattern-based text generation fallback.
 *
 * This file is included (not executed directly) by api_generate.php when all
 * remote AI providers are unavailable. It receives $prompt and $length from
 * the including scope and must return an array with a 'text' key, or null on
 * failure.
 *
 * The generated text is intentionally simple — it is only meant to keep the
 * service responsive when HuggingFace is down or the token is not yet
 * configured, not to replace a real language model.
 */

if (!isset($prompt) || !isset($length)) {
    return null;
}

$prompt  = (string)$prompt;
$length  = max(1, min((int)$length, 1000));

// ---------------------------------------------------------------------------
// Sentence templates grouped by detected topic keywords.
// Each template may contain {TOPIC} which is replaced with a keyword found in
// the prompt, or a generic noun when no keyword matches.
// ---------------------------------------------------------------------------
$topicTemplates = [
    'science' => [
        'Research in this field has advanced significantly over the past decade.',
        'Scientists continue to explore the fundamental principles behind {TOPIC}.',
        'New discoveries are reshaping our understanding of {TOPIC} every year.',
        'The experimental data supports the hypothesis that {TOPIC} plays a key role.',
        'Further studies are needed to fully understand the implications of {TOPIC}.',
        'Collaboration between disciplines has accelerated progress in {TOPIC}.',
    ],
    'technology' => [
        'Modern technology has transformed the way we interact with {TOPIC}.',
        'Engineers are developing new solutions to improve {TOPIC} efficiency.',
        'The latest advancements in {TOPIC} are opening up new possibilities.',
        'Digital innovation continues to reshape {TOPIC} at an unprecedented pace.',
        'Automation and artificial intelligence are changing the landscape of {TOPIC}.',
        'The integration of {TOPIC} into everyday life is becoming more seamless.',
    ],
    'history' => [
        'Throughout history, {TOPIC} has played a significant role in shaping society.',
        'Ancient civilizations had a deep understanding of {TOPIC}.',
        'The historical significance of {TOPIC} cannot be overstated.',
        'Scholars have debated the origins and impact of {TOPIC} for centuries.',
        'The evolution of {TOPIC} reflects broader changes in human culture.',
        'Historical records provide valuable insight into the development of {TOPIC}.',
    ],
    'nature' => [
        'The natural world offers countless examples of {TOPIC} in action.',
        'Ecosystems depend on a delicate balance that includes {TOPIC}.',
        'Biologists have long studied the role of {TOPIC} in the environment.',
        'Climate change is affecting {TOPIC} in ways that scientists are still measuring.',
        'Conservation efforts aim to protect {TOPIC} for future generations.',
        'The diversity of life on Earth is closely linked to {TOPIC}.',
    ],
    'society' => [
        'Society has grappled with questions about {TOPIC} for generations.',
        'Cultural attitudes toward {TOPIC} vary widely across different communities.',
        'Policy makers are increasingly focused on the challenges posed by {TOPIC}.',
        'Education plays a crucial role in shaping how people think about {TOPIC}.',
        'The social impact of {TOPIC} is felt across all demographics.',
        'Community engagement is essential for addressing issues related to {TOPIC}.',
    ],
];

// Generic templates used when no topic keyword is detected.
$genericTemplates = [
    'This is a fascinating subject that deserves careful consideration.',
    'There are many perspectives to consider when thinking about this topic.',
    'Experts in the field have offered a range of insights and opinions.',
    'The complexity of this issue requires a nuanced and thoughtful approach.',
    'Further exploration of this subject reveals many interesting dimensions.',
    'Understanding the context is essential for drawing meaningful conclusions.',
    'The evidence suggests that there is more to this topic than first appears.',
    'A thorough analysis reveals both challenges and opportunities ahead.',
    'Many factors contribute to the current state of affairs in this area.',
    'The discussion continues to evolve as new information comes to light.',
    'It is important to consider multiple viewpoints before forming a conclusion.',
    'The implications of this topic extend far beyond what is immediately obvious.',
];

// ---------------------------------------------------------------------------
// Detect topic from prompt keywords.
// ---------------------------------------------------------------------------
$topicKeywords = [
    'science'    => ['science', 'physics', 'chemistry', 'biology', 'research', 'experiment', 'hypothesis', 'data'],
    'technology' => ['technology', 'computer', 'software', 'hardware', 'digital', 'internet', 'ai', 'robot', 'code', 'program'],
    'history'    => ['history', 'ancient', 'war', 'civilization', 'century', 'historical', 'empire', 'revolution'],
    'nature'     => ['nature', 'animal', 'plant', 'forest', 'ocean', 'climate', 'environment', 'ecosystem', 'species'],
    'society'    => ['society', 'culture', 'social', 'community', 'people', 'government', 'politics', 'education', 'economy'],
];

$promptLower   = strtolower($prompt);
$detectedTopic = null;
$detectedWord  = null;

foreach ($topicKeywords as $topic => $keywords) {
    foreach ($keywords as $kw) {
        if (str_contains($promptLower, $kw)) {
            $detectedTopic = $topic;
            $detectedWord  = $kw;
            break 2;
        }
    }
}

// ---------------------------------------------------------------------------
// Build the generated text by assembling sentences until we reach $length.
// ---------------------------------------------------------------------------
$templates = $detectedTopic !== null ? $topicTemplates[$detectedTopic] : $genericTemplates;
$topicLabel = $detectedWord ?? 'this subject';

// Shuffle so repeated calls produce varied output.
shuffle($templates);

$generated  = '';
$templateCount = count($templates);
$i = 0;

while (strlen($generated) < $length) {
    $sentence = $templates[$i % $templateCount];
    $sentence = str_replace('{TOPIC}', $topicLabel, $sentence);

    if ($generated !== '') {
        $generated .= ' ';
    }
    $generated .= $sentence;

    $i++;

    // Safety valve: stop after cycling through all templates twice.
    if ($i >= $templateCount * 2) {
        break;
    }
}

// Trim to the requested length at a word boundary to avoid cutting mid-word.
if (strlen($generated) > $length) {
    $trimmed = substr($generated, 0, $length);
    $lastSpace = strrpos($trimmed, ' ');
    if ($lastSpace !== false && $lastSpace > $length * 0.5) {
        $trimmed = substr($trimmed, 0, $lastSpace);
    }
    $generated = rtrim($trimmed, ' .,;:') . '.';
}

return ['text' => $generated];
