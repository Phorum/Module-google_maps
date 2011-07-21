{! This template implements the default displaying of the Google }
{! map in the user's profile screen. }

{IF MOD_GOOGLE_MAPS}
  <div class="generic" style="border-top: none">
    <div style="padding: 10px">
      <strong>{LANG->mod_google_maps->ProfileTitle}</strong><br/>
      <div style="height: 300px; border: 1px solid #aaa">
        {MOD_GOOGLE_MAPS}
      </div>
    </div>
  </div>
{/IF}

