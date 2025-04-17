<?php

namespace Doxa\Sitemap;


class Sitemap
{
    protected $channels;

    protected $maps = [];

    protected $alternate_maps = [];

    protected $config;

    protected $existing_channels = [];

    protected $index_updated_at = 0;

    protected $module_data = false;

    protected $module_prefix = '';

    protected $module_index= '';

    protected $module_list = [];

    protected $modules;

    protected $alternate = false;

    protected $main_locale = 'en';

    protected $lang_codes = [
        'en' => 'en',
        'ru' => 'ru-RU',
        'ka' => 'ka-GE',
    ];

    /**
     * Generates the sitemap by retrieving channels, configuring the sitemap,
     * processing module data, creating module tags, and creating the sitemap.
     *
     * @return void
     */
    public function generate()
    {
        $this->getChannels();

        $this->configure();

        foreach($this->config['modules'] as $class){
            $this->resetModuleData();
            $this->getModuleData($class);
            $this->createModuleTags();
        }

        $this->create();
    }

    /**
     * Configures the Sitemap object by setting the config, modules, alternate,
     * and main_locale properties based on the configuration values.
     *
     * @return void
     */
    protected function configure()
    {
        $this->config = config('doxa.sitemap');
        $this->modules = !empty($this->config['modules']) ? $this->config['modules'] : [];
        $this->alternate = config('app.sitemap_alternate') ? true : false;
        $this->main_locale = config('app.locale');
    }

    /**
     * Retrieves and processes the sitemap data for a given module repository.
     *
     * @param string $class The repository class path of the module to retrieve data for.
     * @return void
     */
    protected function getModuleData($class)
    {
        $this->module_data = app($class)->getSitemapData();
        if($this->module_data){
            $this->module_prefix = !empty($this->module_data['prefix']) ? $this->module_data['prefix'] : '';
            $this->module_index = !empty($this->module_data['index']) ? $this->module_data['index'] : '';
            $this->module_list = !empty($this->module_data['list']) ? $this->module_data['list'] : '';
        }

    }

    /**
     * Resets the module data by initializing the existing channels, index updated at,
     * module prefix, module index, and module list to their initial values.
     *
     * @return void
     */
    protected function resetModuleData()
    {
        $this->existing_channels = [];
        $this->index_updated_at = 0;
        $this->module_prefix = $this->module_index = '';
        $this->module_list = [];
    }

    /**
     * Creates module tags for the given list of records.
     *
     * @return void
     */
    protected function createModuleTags()
    {
        if(!empty($this->module_list)){
            foreach($this->module_list as $rec){
                $this->createTag($rec);
            }
        }

        if($this->module_index){
            $this->createModuleIndexPageTags();
        }
    }

/**
     * Creates index tags for each channel and locale.
     *
     * This method iterates over the existing channels and their locales,
     * constructs the URL for each locale, and creates a set of data
     * containing the URL, last modification date, locale, and locale code.
     *
     * The data is then added to the maps and alternate maps arrays.
     *
     * @return void
     */
    protected function createModuleIndexPageTags()
    {
        foreach($this->existing_channels as $channel_id => $locales){
            foreach($locales as $locale_id => $rec){
                $loc = $this->channels[$channel_id]->hostname.'/';

                // -------- locales
                $locale = $this->channels[$channel_id]->locales[$locale_id]->code;
                $locale_code = $this->lang_codes[$locale];
                // -- end -- locales

                if(sizeof($locales) > 1){
                    $loc .= $locale . '/';
                }

                $loc .= $this->module_index;
                $key = $this->module_index;

                $set = [
                    'loc' => $loc,
                    'lastmod' => date(DATE_ATOM, strtotime($this->index_updated_at)),
                    'locale' => $locale,
                    'locale_code' => $locale_code,
                ];

                $this->maps[$this->channels[$channel_id]->name][] = $set;

                if($locale == $this->main_locale){
                    $this->alternate_maps[$this->channels[$rec->channel_id]->name][$key]['main'] = $set;
                } else {
                    $this->alternate_maps[$this->channels[$rec->channel_id]->name][$key]['alternates'][] = $set;
                }
            }
        }
    }

    /**
     * Creates a tag for the given record and updates the index if necessary.
     *
     * @param object $rec The record to create a tag for.
     * @return void
     */
    protected function createTag($rec)
    {
        $this->existing_channels[$rec->channel_id][$rec->locale_id] = $rec;

        // calculate update for index
        if($rec->updated_at > $this->index_updated_at){
            $this->index_updated_at = $rec->updated_at;
        }

        $loc = $this->channels[$rec->channel_id]->hostname.'/';

        $channel = $this->channels[$rec->channel_id];

        // -------- locales
        $locale = $this->channels[$rec->channel_id]->locales[$rec->locale_id]->code;
        $locale_code = $this->lang_codes[$locale];
        // -- end -- locales

        if($channel->multilanguage){
            $loc .= $locale . '/';
        }

        $key = '';

        if($this->module_prefix){
            $loc .= $this->module_prefix. '/';
            $key .= $this->module_prefix. '/';
        }

        $loc .= $rec->url_key;
        $key .= $rec->url_key;

        $set = [
            'loc' => $loc,
            'lastmod' => date(DATE_ATOM, strtotime($rec->updated_at)),
            'locale' => $locale,
            'locale_code' => $locale_code,
        ];

        $this->maps[$this->channels[$rec->channel_id]->name][] = $set;

        if($locale == $this->main_locale){
            $this->alternate_maps[$this->channels[$rec->channel_id]->name][$key]['main'] = $set;
        } else {
            $this->alternate_maps[$this->channels[$rec->channel_id]->name][$key]['alternates'][] = $set;
        }
    }

    /**
     * Retrieves and assigns the channels associated with their IDs.
     *
     * @return void
     */
    protected function getChannels()
    {
        $this->channels = VCL::getChannelsAssocById();
    }

    /**
     * Creates the sitemap based on the provided maps.
     *
     * @throws \Exception If there's an issue with writing to the file.
     * @return void
     */
    protected function create()
    {
        if($this->alternate){
            $this->maps = $this->alternate_maps;
        }
        foreach($this->maps as $channel_name => $tags){
            $this->writeToFile($this->getFilepath($channel_name), $this->render($tags));
        }

    }

    /**
     * Renders the sitemap view based on the provided tags.
     *
     * @param array $tags Collection of tags to be rendered in the sitemap view.
     * @return string The rendered sitemap view.
     */
    public function render($tags): string
    {
        $view_name = 'doxa-sitemap::sitemap';
        if($this->alternate && $this->main_locale){
            $view_name = 'doxa-sitemap::sitemap-alternate';
        }

        return view($view_name)
            ->with(compact('tags'))
            ->render();
    }

    /**
     * Writes the given string to the specified file path.
     *
     * @param string $path The path to the file where the string will be written.
     * @param string $str The string to be written to the file.
     * @return static Returns the current instance.
     */
    public function writeToFile(string $path, $str): static
    {
        file_put_contents($path, $str);
        return $this;
    }

    /**
     * Returns the full path to the sitemap file for a given channel.
     *
     * @param string $channel_name The name of the channel.
     * @return string The full path to the sitemap file.
     */
    public function getFilepath($channel_name): string
    {
        $path = base_path().'/packages/Projects/'.config('app.project_name').'/src/Files/sitemap/'.$channel_name;
        if(!is_dir($path)){
            mkdir($path, 0744, true);
        }
        $path = $path . '/sitemap.xml';
        return $path;
    }
}
