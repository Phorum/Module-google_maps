{! This template implements the default displaying of the Google }
{! map in the user's profile screen. }

{! A little hack to automatically let this template handle the new }
{! Phorum 5.2 "default2" template, without having to create a whole }
{! new template set for it. }
{IF TEMPLATE "default2"}

  {IF MOD_GOOGLE_MAPS}
    <div class="generic" style="border-top: none">
      <div style="padding: 10px">
        <strong>{LANG->mod_google_maps->ProfileTitle}</strong><br/>
        {MOD_GOOGLE_MAPS}
      </div>
    </div>
  {/IF}

{ELSE}

  {IF MOD_GOOGLE_MAPS}
    <div align="center">
      <div class="PhorumStdBlock PhorumNarrowBlock" style="text-align: left">
        <div style="padding: 10px">
          <strong>{LANG->mod_google_maps->ProfileTitle}</strong><br/>
          {MOD_GOOGLE_MAPS}
        </div>
      </div>
    </div>
  {/IF}

{/IF}
