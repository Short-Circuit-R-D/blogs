<?php
/**
 * @var array  $topics          distinct topic/tag strings
 * @var string $topic           currently filtered topic, '' = all
 * @var string $q               current search query, preserved on chip links
 * @var string $basePath        e.g. 'articles.php'
 * @var array  $followedTopics  topics the logged-in user already follows (empty if logged out)
 * @var array  $extraChipParams optional extra query params to preserve, e.g. ['type' => 'articles']
 */
$extraChipParams = $extraChipParams ?? [];
$currentUserRow  = currentUser();
$chipLink = function (string $t) use ($basePath, $q, $extraChipParams) {
    return $basePath . '?' . http_build_query(array_filter($extraChipParams + ['q' => $q, 'topic' => $t]));
};
$returnUrl = $basePath . '?' . http_build_query(array_filter($extraChipParams + ['q' => $q, 'topic' => $topic]));
?>
<div class="topic-chip-row">
  <a class="topic-chip<?= $topic === '' ? ' active' : '' ?>" href="<?= e($basePath . '?' . http_build_query(array_filter($extraChipParams + ['q' => $q]))) ?>">All Topics</a>
  <?php foreach ($topics as $t): $isActive = $topic === $t; $isFollowed = in_array($t, $followedTopics, true); ?>
    <span class="topic-chip-wrap">
      <a class="topic-chip<?= $isActive ? ' active' : '' ?>" href="<?= e($chipLink($t)) ?>"><?= e($t) ?></a>
      <form method="post" action="topic_subscribe.php" class="topic-follow-form">
        <input type="hidden" name="topic" value="<?= e($t) ?>">
        <input type="hidden" name="return" value="<?= e($returnUrl) ?>">
        <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
        <button type="submit" class="topic-follow-btn<?= $isFollowed ? ' following' : '' ?>"
                title="<?= $isFollowed ? 'Following ' . e($t) . ' — click to unfollow' : ($currentUserRow ? 'Get emailed about new ' . e($t) . ' articles' : 'Log in to follow ' . e($t)) ?>">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 3C9.79 3 8 4.79 8 7v3.28c0 .57-.2 1.12-.57 1.55L6 13.5c-.9 1.05-.16 2.68 1.22 2.68h9.56c1.38 0 2.12-1.63 1.22-2.68l-1.43-1.67A2.4 2.4 0 0 1 16 10.28V7c0-2.21-1.79-4-4-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9.5 18a2.5 2.5 0 0 0 5 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        </button>
      </form>
    </span>
  <?php endforeach; ?>
</div>
