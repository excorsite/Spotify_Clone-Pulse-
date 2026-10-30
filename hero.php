<section class="hero">
  <div class="logo"><span class="dot"></span>Pulse</div>
  <div>
    <h2>Your music,<br>in the flow.</h2>
    <p>Discover new tracks, save your favourites and build playlists — all in one clean, glassy player.</p>
    <div class="chips"><span>Discover online</span><span>Playlists</span><span>Liked songs</span></div>
  </div>
  <div class="floaters">
    <div class="fcard c1"></div>
    <div class="fcard c2"></div>
    <div class="fcard c3"></div>
    <div class="mini glass">
      <div class="eq"><?php for ($i = 0; $i < 14; $i++) echo '<i style="height:' . (35 + (($i * 37) % 65)) . '%;animation-delay:' . number_format(($i * 0.13) % 1, 2) . 's"></i>'; ?></div>
      <div><b>Now playing</b><small>Feel the pulse</small></div>
    </div>
  </div>
</section>