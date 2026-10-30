<?php
/* =====================================================================
   4. COLLABORATIVE FILTERING (user-based) + JACCARD SIMILARITY
   -------------------------------------------------------------------
   Idea: "Tapai jasto song like garne aru manche le k k like gareko chha,
   jun tapai le sunnu bhako chhaina, tyo recommend garne."

   Step 1: sabai user ko liked songs ko set banaune.
   Step 2: tapai ra har aru user bich Jaccard similarity nikalne:
              J(A,B) = |A ∩ B| / |A ∪ B|        (0 = kei milena, 1 = same taste)
   Step 3: aru user ko liked song (tapai le like nagareko) lai
           tyo user ko similarity thapera score banaune.
   Step 4: highest score bhayeko songs recommend garne.
   Cold start: tapai le kei like gareko chhaina bhane (ya kasaile milena)
           most-liked (popular) songs dekhaune.
   Time: O(U * L) — U = users, L = ave likes per user.
   ===================================================================== */
function recommend_songs(mysqli $conn, int $uid, int $limit = 12): array
{
    // Step 1: user_id => [song_id => true]
    $likes = [];
    $res = $conn->query('SELECT user_id, song_id FROM likes');
    while ($r = $res->fetch_assoc()) $likes[(int)$r['user_id']][(int)$r['song_id']] = true;
    $mine = $likes[$uid] ?? [];

    $mode = 'popular';
    $ids = [];
    if ($mine) {
        // Step 2 + 3: Jaccard similarity ra score
        $scores = [];
        foreach ($likes as $other => $set) {
            if ($other === $uid) continue;
            $inter = count(array_intersect_key($mine, $set));
            if ($inter === 0) continue;
            $sim = $inter / (count($mine) + count($set) - $inter);
            foreach ($set as $sid => $_) {
                if (!isset($mine[$sid])) $scores[$sid] = ($scores[$sid] ?? 0) + $sim;
            }
        }
        if ($scores) {
            arsort($scores);                       // Step 4: dherai score pahila
            $ids = array_slice(array_keys($scores), 0, $limit);
            $mode = 'collaborative';
        }
    }
    // Cold start / top-up: popular songs (jun user le like gareko chhaina)
    if (count($ids) < $limit) {
        $need = $limit - count($ids);
        $skip = $ids ? implode(',', array_map('intval', $ids)) : '0';
        $q = "SELECT s.id FROM songs s LEFT JOIN likes l ON l.song_id=s.id
              WHERE s.id NOT IN (SELECT song_id FROM likes WHERE user_id=$uid) AND s.id NOT IN ($skip)
              GROUP BY s.id ORDER BY COUNT(l.user_id) DESC, s.id DESC LIMIT $need";
        $p = $conn->query($q);
        while ($r = $p->fetch_assoc()) $ids[] = (int)$r['id'];
    }
    if (!$ids) return ['mode' => $mode, 'songs' => []];

    $in = implode(',', array_map('intval', $ids));
    $rows = $conn->query("SELECT s.id,s.title,s.artist,s.cover,s.file,(l.user_id IS NOT NULL) AS liked
        FROM songs s LEFT JOIN likes l ON l.song_id=s.id AND l.user_id=$uid WHERE s.id IN ($in)")->fetch_all(MYSQLI_ASSOC);
    $byId = [];
    foreach ($rows as $row) $byId[(int)$row['id']] = $row;
    $songs = [];
    foreach ($ids as $id) if (isset($byId[$id])) $songs[] = $byId[$id];   // score order maintain
    return ['mode' => $mode, 'songs' => $songs];
}

/* =====================================================================
   5. MIN-HEAP  (Top-K algorithm)
   -------------------------------------------------------------------
   "Sabai bhanda dherai like bhayeko K wota song" nikalna.
   Sabai song lai sort garera (O(n log n)) hunchha, tara heap le O(n log K) ma gardchha:
   size K ko min-heap rakhne. Naya song ko likes heap ko sabai bhanda sano
   (root) bhanda dherai bhaye root hatayera naya halne.
   Heap = array ma complete binary tree: parent(i) = (i-1)/2, kids = 2i+1, 2i+2
   push/pop: O(log K)
   ===================================================================== */
class MinHeap
{
    private array $a = [];                       // har item: [score, value]
    public function size(): int
    {
        return count($this->a);
    }
    public function peek(): array
    {
        return $this->a[0];
    }
    public function push(array $x): void
    {
        $this->a[] = $x;
        $i = count($this->a) - 1;
        while ($i > 0) {                          // sift-up
            $p = ($i - 1) >> 1;
            if ($this->a[$p][0] <= $this->a[$i][0]) break;
            [$this->a[$p], $this->a[$i]] = [$this->a[$i], $this->a[$p]];
            $i = $p;
        }
    }
    public function pop(): array
    {
        $top = $this->a[0];
        $last = array_pop($this->a);
        if ($this->a) {
            $this->a[0] = $last;
            $i = 0;
            $n = count($this->a);
            while (true) {                        // sift-down
                $l = 2 * $i + 1;
                $r = $l + 1;
                $m = $i;
                if ($l < $n && $this->a[$l][0] < $this->a[$m][0]) $m = $l;
                if ($r < $n && $this->a[$r][0] < $this->a[$m][0]) $m = $r;
                if ($m === $i) break;
                [$this->a[$m], $this->a[$i]] = [$this->a[$i], $this->a[$m]];
                $i = $m;
            }
        }
        return $top;
    }
}

// $scores: [id => score]. Top K (highest score) ko id haru, dherai bata kam tira.
function top_k_ids(array $scores, int $k): array
{
    $h = new MinHeap();
    foreach ($scores as $id => $score) {
        if ($h->size() < $k) $h->push([$score, $id]);
        elseif ($score > $h->peek()[0]) {
            $h->pop();
            $h->push([$score, $id]);
        }
    }
    $asc = [];
    while ($h->size()) $asc[] = $h->pop()[1];
    return array_reverse($asc);
}

function top_k_songs(mysqli $conn, int $uid, int $k = 5): array
{
    $scores = [];
    $res = $conn->query('SELECT song_id, COUNT(*) c FROM likes GROUP BY song_id');
    while ($r = $res->fetch_assoc()) $scores[(int)$r['song_id']] = (int)$r['c'];
    $ids = top_k_ids($scores, $k);
    if (!$ids) return [];
    $in = implode(',', array_map('intval', $ids));
    $rows = $conn->query("SELECT s.id,s.title,s.artist,s.cover,s.file,(l.user_id IS NOT NULL) AS liked
        FROM songs s LEFT JOIN likes l ON l.song_id=s.id AND l.user_id=$uid WHERE s.id IN ($in)")->fetch_all(MYSQLI_ASSOC);
    $byId = [];
    foreach ($rows as $row) {
        $row['likes'] = $scores[(int)$row['id']];
        $byId[(int)$row['id']] = $row;
    }
    $out = [];
    foreach ($ids as $id) if (isset($byId[$id])) $out[] = $byId[$id];
    return $out;
}
