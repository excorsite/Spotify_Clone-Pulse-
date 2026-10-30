/* =====================================================================
   ALGORITHMS USED IN THIS PROJECT (client side)
   1. Fisher–Yates shuffle ........ fair random order, O(n)
   2. Merge sort .................. stable sorting, O(n log n)
   3. Levenshtein distance (DP) ... typo-tolerant search, O(m*n)
   4. Trie ........................ search autocomplete, O(L)
   5. Binary search (lower bound) . A–Z jump bar, O(log n)
   6. LRU cache ................... recently played + search cache, O(1)
   (Collaborative filtering ra Min-Heap Top-K algorithms.php ma chha — server side)
   ===================================================================== */

/* ---- 1. Fisher–Yates shuffle ------------------------------------------
   Pachhi bata suru garera, har position ma bacheko elements bata
   random ek wota swap garne. Har permutation ko probability barabar hunchha,
   ra kunai song duita choti aaudaina. Time O(n), Space O(n) (copy). */
function fisherYates(arr) {
  const a = arr.slice();
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

/* ---- 2. Merge sort ----------------------------------------------------
   Divide: array lai aadha aadha garne. Conquer: dubai bhaag sort garne.
   Combine: dui sorted list lai merge garne. Stable (barabar elements ko
   order change hudaina). Time O(n log n) sabai case ma, Space O(n). */
function mergeSort(arr, cmp) {
  if (arr.length <= 1) return arr;
  const mid = arr.length >> 1;
  const L = mergeSort(arr.slice(0, mid), cmp);
  const R = mergeSort(arr.slice(mid), cmp);
  const out = [];
  let i = 0,
    j = 0;
  while (i < L.length && j < R.length)
    out.push(cmp(L[i], R[j]) <= 0 ? L[i++] : R[j++]);
  while (i < L.length) out.push(L[i++]);
  while (j < R.length) out.push(R[j++]);
  return out;
}

/* ---- 3. Levenshtein distance (Dynamic Programming) --------------------
   Dui string bich ko "edit distance": insert / delete / replace gareko
   minimum kati step le a lai b banauchha. Jastai kitten -> sitting = 3.
   Table ko duita row matra rakhera memory O(min) ma gareko. Time O(m*n). */
function levenshtein(a, b) {
  if (a === b) return 0;
  if (!a.length) return b.length;
  if (!b.length) return a.length;
  let prev = Array.from({ length: b.length + 1 }, (_, j) => j);
  for (let i = 1; i <= a.length; i++) {
    const cur = [i];
    for (let j = 1; j <= b.length; j++) {
      cur[j] = Math.min(
        prev[j] + 1, // delete
        cur[j - 1] + 1, // insert
        prev[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1), // replace
      );
    }
    prev = cur;
  }
  return prev[b.length];
}

/* Score: sano = ramro match. -1 starts-with, 0 contains, 1+ = typo distance.
   Infinity = match bhayena. Allowed typos = query ko length / 3. */
function fuzzyScore(text, query) {
  text = text.toLowerCase();
  query = query.toLowerCase();
  if (text.startsWith(query)) return -1;
  if (text.includes(query)) return 0;
  const limit = Math.max(1, Math.floor(query.length / 3));
  let best = Infinity;
  for (const w of text.split(/[^a-z0-9\u0900-\u097f]+/)) {
    if (!w) continue;
    best = Math.min(
      best,
      levenshtein(w, query),
      levenshtein(w.slice(0, query.length), query),
    );
  }
  return best <= limit ? best : Infinity;
}

/* Fuzzy search: title ra artist dubai ma khojne, best match pahila (merge sort le). */
function fuzzySearch(items, query) {
  const scored = [];
  for (const s of items) {
    const sc = Math.min(
      fuzzyScore(s.title, query),
      fuzzyScore(s.artist, query),
    );
    if (sc !== Infinity) scored.push({ s, sc });
  }
  return mergeSort(scored, (x, y) => x.sc - y.sc).map((x) => x.s);
}

/* ---- 4. Trie (prefix tree) -------------------------------------------
   Har character ek node. "bl" lekhda "blinding", "blue" jasta words tyo
   prefix bata afai aauchha. Insert O(L), prefix search O(L + results),
   L = word ko length. (Normal list ma khojda har choti O(n x L) lagthyo.) */
class Trie {
  constructor() {
    this.root = { kids: {}, labels: null };
  }
  insert(word, label) {
    let n = this.root;
    for (const ch of word)
      n = n.kids[ch] || (n.kids[ch] = { kids: {}, labels: null });
    (n.labels || (n.labels = new Set())).add(label);
  }
  suggest(prefix, limit = 6) {
    let n = this.root;
    for (const ch of prefix) {
      n = n.kids[ch];
      if (!n) return [];
    }
    const out = new Set();
    const dfs = (node) => {
      // prefix node bhitra ko sabai words (DFS)
      if (out.size >= limit) return;
      if (node.labels)
        for (const l of node.labels) {
          out.add(l);
          if (out.size >= limit) return;
        }
      for (const k in node.kids) dfs(node.kids[k]);
    };
    dfs(n);
    return [...out];
  }
}
function buildTrie(songs) {
  const t = new Trie();
  for (const s of songs)
    for (const [text, label] of [
      [s.title, s.title],
      [s.artist, s.artist],
    ])
      for (const w of text.toLowerCase().split(/[^a-z0-9\u0900-\u097f]+/))
        if (w) t.insert(w, label);
  return t;
}

/* ---- 5. Binary search (lower bound) -----------------------------------
   Sorted array ma "key bhanda sano chhaina" bhanne pahilo position khojne.
   Har step ma aadha khojne ठाउँ hatchha. Time O(log n) (linear search O(n)). */
function lowerBound(arr, isLess) {
  let lo = 0,
    hi = arr.length;
  while (lo < hi) {
    const mid = (lo + hi) >> 1;
    if (isLess(arr[mid])) lo = mid + 1;
    else hi = mid;
  }
  return lo;
}

/* ---- 6. LRU Cache (Least Recently Used) -------------------------------
   Capacity bharine bitikai sabai bhanda purano (kam use bhayeko) hatchha.
   JavaScript Map le insertion order yaad rakhchha, tesaile get/set dubai O(1). */
class LRUCache {
  constructor(cap) {
    this.cap = cap;
    this.map = new Map();
  }
  get(k) {
    if (!this.map.has(k)) return undefined;
    const v = this.map.get(k);
    this.map.delete(k);
    this.map.set(k, v); // naya (recent) banaune
    return v;
  }
  set(k, v) {
    if (this.map.has(k)) this.map.delete(k);
    else if (this.map.size >= this.cap)
      this.map.delete(this.map.keys().next().value); // sabai bhanda purano hatau
    this.map.set(k, v);
  }
}

if (typeof module !== "undefined")
  module.exports = {
    fisherYates,
    mergeSort,
    levenshtein,
    fuzzyScore,
    fuzzySearch,
    Trie,
    buildTrie,
    lowerBound,
    LRUCache,
  };
