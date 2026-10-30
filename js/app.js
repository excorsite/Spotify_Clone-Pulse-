const $ = (id) => document.getElementById(id);
const audio = new Audio();
let songs = [],
  raw = [],
  recs = [],
  recMode = "",
  origQueue = [],
  queue = [],
  cur = -1,
  view = "all",
  plId = 0,
  playlists = [],
  shuffle = false,
  repeat = 0; // repeat: 0 off, 1 all, 2 one
const HUES = [150, 25, 280, 200, 340, 60, 110, 230];
const searchCache = new LRUCache(15); // LRU cache: online search results (network call bachauchha)
const recent = new LRUCache(8); // LRU cache: recently played songs
const RKEY = "pulse_recent_" + (window.UID || 0);
try {
  JSON.parse(localStorage.getItem(RKEY) || "[]").forEach((x) =>
    recent.set(String(x.id), x),
  );
} catch (e) {}
const saveRecent = () => {
  try {
    localStorage.setItem(RKEY, JSON.stringify([...recent.map.values()]));
  } catch (e) {}
};
const recentList = () => [...recent.map.values()].reverse(); // naya pahila
let trie = new Trie(),
  tops = [],
  recentSnap = [];
const LETTERS = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("");
const titleKey = (s) => s.title.trim().toLowerCase();

const ic = (n, c = "") => `<svg class="ic ${c}"><use href="#i-${n}"/></svg>`;
const esc = (s) =>
  String(s).replace(
    /[&<>"']/g,
    (c) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[
        c
      ],
  );
const fmt = (t) =>
  isNaN(t)
    ? "0:00"
    : Math.floor(t / 60) + ":" + String(Math.floor(t % 60)).padStart(2, "0");
const isUrl = (f) => /^https?:/.test(f);
const audioSrc = (f) =>
  isUrl(f) ? f : "uploads/songs/" + encodeURIComponent(f);
const coverUrl = (c) =>
  (isUrl(c) ? c : "uploads/covers/" + encodeURIComponent(c)).replace(
    /'/g,
    "%27",
  );
const grad = (n) => {
  const h = HUES[Math.abs(n) % 8];
  return `linear-gradient(135deg,hsl(${h} 70% 55%),hsl(${(h + 40) % 360} 60% 22%))`;
};
const greeting = () => {
  const h = new Date().getHours();
  return h < 12 ? "Good morning" : h < 17 ? "Good afternoon" : "Good evening";
};
// cover: image (agar chha) + gradient fallback
const cv = (s, cls = "") => {
  const st = s.cover
    ? `background-image:url('${coverUrl(s.cover)}'),${grad(+s.id || 0)}`
    : `background:${grad(+s.id || 0)}`;
  return `<div class="cv ${cls}" style="${esc(st)}">${s.cover ? "" : ic("music")}</div>`;
};

async function api(a, params = {}, post = false) {
  let url = "api.php?a=" + a,
    opt = {};
  if (post) {
    const fd = new FormData();
    for (const k in params) fd.append(k, params[k]);
    opt = { method: "POST", body: fd };
  } else url += "&" + new URLSearchParams(params);
  const r = await fetch(url, opt);
  if (r.status === 401) location = "login.php";
  return r.json();
}

/* ---------------- toast + modal ---------------- */
function toast(m) {
  const t = $("toast");
  t.textContent = m;
  t.classList.add("show");
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.remove("show"), 2200);
}
function modal(html) {
  $("mbody").innerHTML = html;
  $("modal").hidden = false;
}
function closeModal() {
  $("modal").hidden = true;
}
function newPlaylistModal(sid = 0) {
  modal(`<h3>New playlist</h3><input id="plName" placeholder="Playlist name" maxlength="60">
    <div class="mact"><button class="btn ghost" data-close>Cancel</button><button class="btn" id="plOk" data-sid="${sid}">Create</button></div>`);
  $("plName").focus();
}
function pickPlaylist(sid) {
  if (!playlists.length) return newPlaylistModal(sid);
  modal(`<h3>Add to playlist</h3><div class="pick">${playlists
    .map(
      (p) =>
        `<button data-pid="${p.id}" data-sid="${sid}">${cv({ id: p.id }, "xs")}${esc(p.name)}</button>`,
    )
    .join("")}</div>
    <div class="mact"><button class="btn ghost" data-newpl="${sid}">＋ New playlist</button><button class="btn ghost" data-close>Close</button></div>`);
}
$("modal").addEventListener("click", async (e) => {
  const t = e.target;
  if (t.id === "modal" || t.closest("[data-close]")) return closeModal();
  const p = t.closest("[data-pid]");
  if (p) {
    const r = await api(
      "pl_add",
      { playlist_id: p.dataset.pid, song_id: p.dataset.sid },
      true,
    );
    closeModal();
    return toast(r.ok ? "Added to playlist ✓" : "Error");
  }
  const n = t.closest("[data-newpl]");
  if (n) return newPlaylistModal(n.dataset.newpl);
  if (t.id === "plOk") {
    const name = $("plName").value.trim();
    if (!name) return;
    await api("pl_create", { name }, true);
    await loadPlaylists();
    if (+t.dataset.sid) {
      await api(
        "pl_add",
        {
          playlist_id: playlists[playlists.length - 1].id,
          song_id: t.dataset.sid,
        },
        true,
      );
      toast("Playlist created & song added ✓");
    } else toast("Playlist created ✓");
    closeModal();
    if (view === "all") render();
  }
});
$("modal").addEventListener("keydown", (e) => {
  if (e.key === "Enter" && e.target.id === "plName") $("plOk").click();
});

/* ---------------- data + render ---------------- */
async function loadPlaylists() {
  playlists = await api("playlists");
  $("playlists").innerHTML = playlists
    .map(
      (p) =>
        `<a href="#" class="pl" data-id="${p.id}">${cv({ id: p.id }, "sm")}<span class="pn">${esc(p.name)}<small>Playlist</small></span></a>`,
    )
    .join("");
}

const SORTS = {
  new: null,
  title: (a, b) => titleKey(a).localeCompare(titleKey(b)),
  titleDesc: (a, b) => titleKey(b).localeCompare(titleKey(a)),
  artist: (a, b) =>
    a.artist.localeCompare(b.artist) || a.title.localeCompare(b.title),
};
const sortSongs = (l) =>
  SORTS[$("sort").value] ? mergeSort(l, SORTS[$("sort").value]) : l; // Merge sort (algorithms.js)
// search chha bhane Levenshtein fuzzy search, nabhaye sort
function arrange() {
  const q = $("search").value.trim();
  songs = view !== "online" && q ? fuzzySearch(raw, q) : sortSongs(raw);
  render();
}
async function loadSongs() {
  $("content").innerHTML = '<p class="empty">Loading...</p>';
  const q = $("search").value.trim();
  let r;
  if (view === "online") {
    const hit = searchCache.get(q); // LRU hit => network call chaidaina
    r = hit || (await api("discover", { q }));
    if (!hit && Array.isArray(r)) searchCache.set(q, r);
  } else r = await api("songs", { q: "", view, id: plId });
  if (!Array.isArray(r)) {
    raw = songs = [];
    $("content").innerHTML = `<p class="empty">${esc(r.error || "Error")}</p>`;
    return;
  }
  raw = r;
  recs = [];
  tops = [];
  trie = buildTrie(raw); // Trie banaune
  if (view === "all") {
    // Collaborative filtering (algorithms.php)
    const [rr, tt] = await Promise.all([api("recommend"), api("top")]); // Collaborative filtering + Min-Heap Top-K
    if (rr && rr.songs) {
      recs = rr.songs;
      recMode = rr.mode;
    }
    if (Array.isArray(tt)) tops = tt;
  }
  arrange();
}

const row = (s, i) => `
  <div class="track" data-i="${i}" data-id="${s.id}">
    <button class="num" data-act="play">${i + 1}</button>${cv(s, "sm")}
    <div class="meta"><b>${esc(s.title)}</b><small>${esc(s.artist)}</small></div>
    <button class="ibtn like ${+s.liked ? "on" : ""}" data-act="like" data-id="${s.id}" title="Like">${ic("heart")}</button>
    <button class="ibtn add" data-act="add" title="Add to playlist">${ic("plus")}</button>
    ${view === "pl" ? `<button class="ibtn rm" data-act="rm" title="Remove from playlist">${ic("x")}</button>` : ""}
  </div>`;
const card = (s, i, a = "data-i") => `
  <div class="card" ${a}="${i}" data-id="${s.id}">
    <div class="cwrap">${cv(s, "sq")}<button class="fab" data-act="play">${ic("play", "fill")}</button></div>
    <b>${esc(s.title)}</b><small>${esc(s.artist)}</small>
  </div>`;

const chartRow = (s, i) => `
  <div class="track" data-ti="${i}" data-id="${s.id}"><span class="rank">${i + 1}</span>${cv(s, "sm")}
    <div class="meta"><b>${esc(s.title)}</b><small>${esc(s.artist)}</small></div>
    <small class="lk">${ic("heart", "fill")} ${s.likes}</small></div>`;

// Binary search: title-sorted `songs` ma letter bata suru hune pahilo song ko index
const firstIndexOf = (L) =>
  lowerBound(songs, (s) => titleKey(s).localeCompare(L.toLowerCase()) < 0);
function azBar() {
  if (
    $("sort").value !== "title" ||
    $("search").value.trim() ||
    songs.length < 2
  )
    return "";
  return (
    '<div class="az">' +
    LETTERS.map((L) => {
      const i = firstIndexOf(L),
        has =
          i < songs.length && titleKey(songs[i]).startsWith(L.toLowerCase());
      return `<button data-letter="${L}" class="${has ? "" : "dim"}">${L}</button>`;
    }).join("") +
    "</div>"
  );
}

function render() {
  const el = $("content");
  if (!songs.length) {
    el.innerHTML =
      '<p class="empty">Kunai song bhetiyena. "Discover" ma khojnus.</p>';
    return;
  }
  let h = "";
  if (view === "all" && !$("search").value.trim()) {
    h += `<div class="tiles"><button class="tile" data-go="liked"><span class="cv lg heart">${ic("heart", "fill")}</span><span>Liked Songs</span></button>`;
    h +=
      playlists
        .slice(0, 5)
        .map(
          (p) =>
            `<button class="tile" data-go="pl" data-id="${p.id}">${cv({ id: p.id }, "lg")}<span>${esc(p.name)}</span></button>`,
        )
        .join("") + "</div>";
    if (recs.length) {
      const note =
        recMode === "collaborative"
          ? "Tapai jasto taste bhayeka listeners le like gareka songs (Collaborative Filtering)"
          : "Popular songs — kehi song like garnus, recommendation tapai ko taste anusar hunchha";
      h +=
        `<h2>Recommended for you</h2><p class="algo">${note}</p><div class="grid">` +
        recs.map((s, i) => card(s, i, "data-ri")).join("") +
        "</div>";
    }
    if (tops.length)
      h +=
        '<h2>Top charts</h2><p class="algo">Sabai bhanda dherai like bhayeka songs (Min-Heap Top-K)</p><div class="chart">' +
        tops.map(chartRow).join("") +
        "</div>";
    recentSnap = recentList();
    if (recentSnap.length)
      h +=
        '<h2>Recently played</h2><p class="algo">Hal ka sunieka songs (LRU Cache)</p><div class="grid">' +
        recentSnap.map((s, i) => card(s, i, "data-ci")).join("") +
        "</div>";
    h += "<h2>All tracks</h2>";
  }
  el.innerHTML =
    h + azBar() + '<div class="tracks">' + songs.map(row).join("") + "</div>";
  refreshPlaying();
}

function refreshPlaying() {
  const id = cur >= 0 ? queue[cur].id : null,
    on = !audio.paused;
  document.body.classList.toggle("is-playing", on);
  document
    .querySelectorAll(
      "#content [data-i],#content [data-ri],#content [data-ci],#content [data-ti]",
    )
    .forEach((el) => {
      const same = el.dataset.id == id;
      el.classList.toggle("playing", same);
      const n = el.querySelector(".num");
      if (n) n.innerHTML = same && on ? ic("pause", "fill") : +el.dataset.i + 1;
    });
  $("play").innerHTML = ic(on ? "pause" : "play", "fill");
}

$("content").addEventListener("click", (e) => {
  const go = e.target.closest("[data-go]");
  if (go)
    return go.dataset.go === "pl"
      ? openPlaylist(go.dataset.id)
      : setView("liked");
  const lt = e.target.closest("[data-letter]");
  if (lt) {
    // A–Z jump (Binary search)
    const L = lt.dataset.letter,
      i = firstIndexOf(L),
      row = $("content").querySelector(`.track[data-i="${i}"]`);
    if (row && titleKey(songs[i]).startsWith(L.toLowerCase())) {
      row.scrollIntoView({ behavior: "smooth", block: "center" });
      row.classList.add("flash");
      setTimeout(() => row.classList.remove("flash"), 1500);
    } else toast(`"${L}" bata suru hune song chhaina`);
    return;
  }
  const x = e.target.closest("[data-ri],[data-ci],[data-ti]");
  if (x) {
    const set =
      x.dataset.ri !== undefined
        ? recs
        : x.dataset.ci !== undefined
          ? recentSnap
          : tops;
    const i = +(x.dataset.ri ?? x.dataset.ci ?? x.dataset.ti);
    if (cur >= 0 && queue[cur].id == x.dataset.id) togglePlay();
    else startQueue(set, i);
    return;
  }
  const item = e.target.closest("[data-i]");
  if (!item) return;
  const a = (e.target.closest("[data-act]") || {}).dataset?.act,
    i = +item.dataset.i,
    id = item.dataset.id;
  if (a === "like") toggleLike(id);
  else if (a === "add") pickPlaylist(id);
  else if (a === "rm")
    api("pl_remove", { playlist_id: plId, song_id: id }, true).then(() => {
      raw = raw.filter((s) => s.id != id);
      arrange();
    });
  else if (cur >= 0 && queue[cur].id == id) togglePlay();
  else playFromList(i);
});

async function toggleLike(id) {
  const r = await api("like", { song_id: id }, true);
  [songs, raw, recs, tops, queue].forEach((arr) =>
    arr.forEach((s) => {
      if (s.id == id) s.liked = r.liked;
    }),
  );
  recent.map.forEach((s) => {
    if (s.id == id) s.liked = r.liked;
  });
  saveRecent();
  document
    .querySelectorAll(`.like[data-id="${id}"]`)
    .forEach((b) => b.classList.toggle("on", !!r.liked));
}
$("fLike").onclick = () => {
  if (cur >= 0) toggleLike(queue[cur].id);
};

/* ---------------- views / navigation ---------------- */
function setView(v, id = 0, name = "") {
  view = v;
  plId = id;
  $("search").value = "";
  $("title").textContent = {
    all: greeting(),
    online: "Discover Online",
    liked: "Liked Songs",
    pl: name,
  }[v];
  $("search").placeholder =
    v === "online"
      ? "Search any song or artist online..."
      : "Search songs or artists...";
  $("plDel").hidden = v !== "pl";
  document
    .querySelectorAll(".side .active,.mnav .active")
    .forEach((n) => n.classList.remove("active"));
  document
    .querySelectorAll(
      v === "pl" ? `.pl[data-id="${id}"]` : `[data-view="${v}"]`,
    )
    .forEach((n) => n.classList.add("active"));
  loadSongs();
}
function openPlaylist(id) {
  const p = playlists.find((x) => x.id == id);
  if (p) setView("pl", p.id, p.name);
}
document.querySelectorAll("[data-view]").forEach(
  (a) =>
    (a.onclick = (e) => {
      e.preventDefault();
      setView(a.dataset.view);
    }),
);
$("playlists").addEventListener("click", (e) => {
  const a = e.target.closest(".pl");
  if (a) {
    e.preventDefault();
    openPlaylist(a.dataset.id);
  }
});
$("newPl").onclick = () => newPlaylistModal();
$("plDel").onclick = async () => {
  if (!confirm("Yo playlist delete garne?")) return;
  await api("pl_delete", { playlist_id: plId }, true);
  await loadPlaylists();
  setView("all");
};
let timer;
$("search").oninput = () => {
  clearTimeout(timer);
  timer = setTimeout(
    view === "online" ? loadSongs : arrange,
    view === "online" ? 700 : 150,
  );
};
$("sort").onchange = arrange;

// Trie autocomplete suggestions
const sugg = $("sugg");
function showSugg() {
  const q = $("search").value.toLowerCase().trim(),
    last = q.split(/\s+/).pop();
  const list = q
    ? trie.suggest(last, 6).filter((l) => l.toLowerCase() !== q)
    : [];
  sugg.hidden = !list.length;
  sugg.innerHTML = list
    .map(
      (l) =>
        `<button type="button" data-s="${esc(l)}">${ic("search")}${esc(l)}</button>`,
    )
    .join("");
}
sugg.addEventListener("mousedown", (e) => {
  const b = e.target.closest("[data-s]");
  if (!b) return;
  e.preventDefault();
  $("search").value = b.dataset.s;
  sugg.hidden = true;
  view === "online" ? loadSongs() : arrange();
});
$("search").addEventListener("input", showSugg);
$("search").addEventListener("blur", () =>
  setTimeout(() => {
    sugg.hidden = true;
  }, 120),
);

/* ---------------- player ---------------- */
// Queue suru garne. Shuffle on bhaye Fisher–Yates le queue shuffle garchha (kunai song repeat hudaina)
function startQueue(list, i) {
  origQueue = list.slice();
  if (shuffle) {
    queue = [list[i], ...fisherYates(list.filter((_, k) => k !== i))];
    playQ(0);
  } else {
    queue = list.slice();
    playQ(i);
  }
}
const playFromList = (i) => startQueue(songs, i);
const playFromRecs = (i) => startQueue(recs, i);
function playQ(i) {
  if (i < 0 || i >= queue.length) return;
  cur = i;
  const s = queue[i];
  audio.src = audioSrc(s.file);
  audio.play().catch(() => {});
  recent.set(String(s.id), s);
  saveRecent(); // LRU: recently played ma halne
  api("played", { song_id: s.id }, true); // admin dashboard ko lagi: k bajayo
  updateNP();
  refreshPlaying();
  if ("mediaSession" in navigator)
    navigator.mediaSession.metadata = new MediaMetadata({
      title: s.title,
      artist: s.artist,
    });
}
function updateNP() {
  const s = queue[cur];
  if (!s) return;
  $("npTitle").textContent = $("fTitle").textContent = s.title;
  $("npArtist").textContent = $("fArtist").textContent = s.artist;
  $("discImg").innerHTML = cv(s, "full");
  $("fCover").innerHTML = cv(s, "md");
  const fl = $("fLike");
  fl.dataset.id = s.id;
  fl.classList.toggle("on", !!+s.liked);
  const up = [];
  for (let k = 1; k <= 3 && k < queue.length; k++)
    up.push([(cur + k) % queue.length, queue[(cur + k) % queue.length]]);
  $("upnext").innerHTML = up
    .map(
      ([qi, u]) =>
        `<button class="up" data-q="${qi}">${cv(u, "sm")}<div><div>${esc(u.title)}</div><small>${esc(u.artist)}</small></div></button>`,
    )
    .join("");
  document.title = s.title + " • Pulse";
}
$("upnext").onclick = (e) => {
  const b = e.target.closest("[data-q]");
  if (b) playQ(+b.dataset.q);
};

function nextIndex() {
  // shuffle ma sabai song baji sakepachhi naya Fisher–Yates round
  if (cur + 1 >= queue.length && shuffle && queue.length > 1) {
    const last = queue[cur].id;
    do {
      queue = fisherYates(queue);
    } while (queue[0].id === last);
    cur = -1;
  }
  return (cur + 1) % queue.length;
}
function togglePlay() {
  if (cur < 0) {
    if (songs.length) playFromList(0);
    return;
  }
  audio.paused ? audio.play() : audio.pause();
}
$("play").onclick = togglePlay;
$("next").onclick = () => {
  if (queue.length) playQ(nextIndex());
};
$("prev").onclick = () => {
  if (queue.length)
    playQ(
      audio.currentTime > 3 ? cur : (cur - 1 + queue.length) % queue.length,
    );
};
$("shuffle").onclick = (e) => {
  shuffle = !shuffle;
  e.currentTarget.classList.toggle("on", shuffle);
  if (cur >= 0 && queue.length) {
    const now = queue[cur];
    if (shuffle) {
      queue = [now, ...fisherYates(queue.filter((_, k) => k !== cur))];
      cur = 0;
    } else {
      queue = origQueue.slice();
      cur = Math.max(
        0,
        queue.findIndex((s) => s.id == now.id),
      );
    }
    updateNP();
  }
  toast(shuffle ? "Shuffle on (Fisher–Yates)" : "Shuffle off");
};
$("repeat").onclick = (e) => {
  repeat = (repeat + 1) % 3;
  e.currentTarget.innerHTML = ic(repeat === 2 ? "repeat1" : "repeat");
  e.currentTarget.classList.toggle("on", repeat > 0);
};
audio.onplay = audio.onpause = refreshPlaying;
audio.onended = () => {
  if (repeat === 2) return playQ(cur);
  if (cur < queue.length - 1 || repeat === 1) playQ(nextIndex());
  else refreshPlaying();
};
audio.onerror = () => {
  if (cur >= 0) {
    toast("Yo song bajena, next ma jadai chhu...");
    setTimeout(() => $("next").click(), 1200);
  }
};
audio.ontimeupdate = () => {
  const p = audio.duration ? (audio.currentTime / audio.duration) * 100 : 0;
  $("seek").value = p;
  $("seek").style.setProperty("--p", p + "%");
  $("cur").textContent = fmt(audio.currentTime);
  $("dur").textContent = fmt(audio.duration);
};
$("seek").oninput = (e) => {
  if (audio.duration)
    audio.currentTime = (e.target.value / 100) * audio.duration;
};
$("vol").oninput = (e) => {
  audio.volume = e.target.value;
  e.target.style.setProperty("--p", e.target.value * 100 + "%");
};
audio.volume = 0.8;

if ("mediaSession" in navigator) {
  navigator.mediaSession.setActionHandler("play", () => audio.play());
  navigator.mediaSession.setActionHandler("pause", () => audio.pause());
  navigator.mediaSession.setActionHandler("nexttrack", () => $("next").click());
  navigator.mediaSession.setActionHandler("previoustrack", () =>
    $("prev").click(),
  );
}

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeModal();
  if (!$("modal").hidden || /INPUT|TEXTAREA/.test(e.target.tagName)) return;
  if (e.code === "Space") {
    e.preventDefault();
    togglePlay();
  }
  if (e.code === "ArrowRight") $("next").click();
  if (e.code === "ArrowLeft") $("prev").click();
});

/* ---------------- init ---------------- */
$("eq").innerHTML = Array.from(
  { length: 24 },
  (_, i) =>
    `<i style="height:${40 + ((i * 37) % 60)}%;animation-delay:${((i * 0.13) % 1).toFixed(2)}s;animation-duration:${(0.6 + ((i * 7) % 6) / 10).toFixed(1)}s"></i>`,
).join("");
(async () => {
  await loadPlaylists();
  setView("all");
})();
setInterval(() => api("ping"), 45000); // 'online' dekhauna heartbeat
