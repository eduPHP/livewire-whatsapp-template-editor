<?php

namespace WaTemplates;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use WaTemplates\Livewire\Panels\BodyPanel;
use WaTemplates\Livewire\Panels\ButtonsPanel;
use WaTemplates\Livewire\Panels\CarouselPanel;
use WaTemplates\Livewire\Panels\FooterPanel;
use WaTemplates\Livewire\Panels\HeaderPanel;
use WaTemplates\Livewire\Panels\MetadataPanel;
use WaTemplates\Livewire\TemplateEditor;
use WaTemplates\Livewire\TemplateVisualizer;

class TemplatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'wa-templates');
    }

    public function boot(): void
    {
        /**
         * Anonymous components resolve as `<x-wa-templates::phone.frame>`.
         * `loadViewsFrom` alone does not register the component namespace, so
         * without this every `<x-wa-templates::…>` tag renders as literal text.
         */
        Blade::anonymousComponentNamespace(__DIR__.'/../resources/views/components', 'wa-templates');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/wa-templates'),
        ], 'wa-templates-views');

        if (! class_exists(Livewire::class)) {
            return;
        }

        /**
         * Hyphenated, never `wa-templates::editor`: Livewire reads `::` as its
         * own component-namespace separator, so a `::` alias silently fails to
         * resolve — see `modules/WhatsApp/WhatsAppServiceProvider.php`, where
         * exactly that cost a working component.
         */
        Livewire::component('wa-template-editor', TemplateEditor::class);
        Livewire::component('wa-template-visualizer', TemplateVisualizer::class);
        Livewire::component('wa-template-metadata-panel', MetadataPanel::class);
        Livewire::component('wa-template-header-panel', HeaderPanel::class);
        Livewire::component('wa-template-body-panel', BodyPanel::class);
        Livewire::component('wa-template-footer-panel', FooterPanel::class);
        Livewire::component('wa-template-buttons-panel', ButtonsPanel::class);
        Livewire::component('wa-template-carousel-panel', CarouselPanel::class);
    }
}
