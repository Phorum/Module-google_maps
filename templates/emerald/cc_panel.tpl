{IF ERROR}<div class="attention">{ERROR}</div>{/IF}
{IF OKMSG}<div class="information">{OKMSG}</div>{/IF}

<form action="{URL->LOCATION_CONFIGURE}" method="post">
  {POST_VARS}

  <div class="generic">

    <div style="margin-bottom: 1em">
      {LANG->mod_google_maps->CCIntroduction}
    </div>

    <div style="height:400px">
      {MOD_GOOGLE_MAPS}
    </div>

    <div style="margin: 2em 0 0 0">
      <input type="submit" value="{LANG->SaveChanges}" />
    </div>

  </div>
</form>

