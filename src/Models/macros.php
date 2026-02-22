<?php

Html::macro('lgBloc', function ($lang, $inline = false) {

        if (config('app.locales')) {

            $html = null;
            $locale = empty($lang) ? config('app.fallback_locale') : $lang;

            $html .= '<div id="lgEdit'.($inline ? 'Inline' : null).'">';

            foreach (explode(',', config('app.locales')) as $key => $virgo) {
                $html .= url(Request::path().'?editLg='.$virgo, $virgo, array('class' => $virgo.($locale == $virgo ? ' active' : null).' pull-left'));
            }
            $html .= '</div>';

            return $html;
        }

     });

?>