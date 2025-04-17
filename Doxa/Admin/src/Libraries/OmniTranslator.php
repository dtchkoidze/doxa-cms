<?php

namespace Doxa\Admin\Libraries;

class OmniTranslator
{
    public function trans()
    {
        $args = func_get_args();

        if(sizeof($args) == 1){
            $module = 'default';
        } else {
            $module = array_shift($args);
        }

        //dump(['module' => $module, 'str' => implode('.', $args)]);

        $variants = [
            'admin::app.'.$module.'.'.implode('.', $args),
            'admin::app.default.'.implode('.', $args),
            'admin::app.'.$module.'.'.implode('.', $args),
        ];

        

        foreach($variants as $variant) {
            $package = config('doxa.modules.'.$module);
            //Clog::write('omniTranslator','$package: '.json_encode($package));
            $translation = trans($variant, [
                'title' => !empty($package['title']) ? $package['title'] : '',
                'title_plural' =>  !empty($package['title_plural']) ? $package['title_plural'] : '',
            ], app()->getLocale());

            if($translation != $variant) {
                return $translation;
            }
        }
        //dd('NOT found',$translation);
        return $translation;
    }

    public function transPageTitle()
    {
        $trans = $this->trans(...func_get_args());
        return 'Doxa - '.$trans;
    }

    public function moduleTrans()
    {
        //return $this->trans(...func_get_args());
        $test_key = 'data-updated';  

        $args = func_get_args();

        $module = array_shift($args);

        $key = implode('.', $args);

        //Clog::write('omniTranslator','$key: '.$key);

        $path = 'doxa.modules.'.$module.'.translations.'.app()->getLocale().'.'.implode('.', $args);

        //Clog::write('omniTranslator','moduleTrans() 001 $path: '.$path);

        //dump($path);

        $value = config($path);

        //Clog::write('omniTranslator','001 $value: '.$value);

        // if($key == $test_key) {
        //     Clog::write('omniTranslator','key: '.$key.' lng: '.app()->getLocale().' config: '.json_encode(config('doxa.package.'.$module)));
        //     Clog::write('omniTranslator','$path: '.$path);
        //     //dump(config('doxa.package.service'));
        //     Clog::write('omniTranslator','moduleTrans() $value: '.$value);
        //     //dump($path);
        //     //dump($value);
        // }

        //dump('value: '.$value);
        //dd($args);

        if(!$value){
            $path = 'doxa.package.'.$module.'.translations.en.'.implode('.', $args);
            //Clog::write('omniTranslator','moduleTrans() 002 $path: '.$path);
            $value = config($path);
            //Clog::write('omniTranslator','002 $value: '.$value);
        }    

        if(!$value){
            $value = omniTrans(...func_get_args());
            // Clog::write('omniTranslator','got from omniTrans(): '.$value);
            // if($key == $test_key) {
            //     Clog::write('omniTranslator','moduleTrans() $value not found, lets get with omniTrans()');
            //     Clog::write('omniTranslator','omniTrans() $value: '.$value);
            // }
        }

        return $value;
    }

}

