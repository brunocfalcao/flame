<?php

namespace Brunocfalcao\Flame\Renderers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Brunocfalcao\Flame\Exceptions\FlameException;

class Panel extends Renderer
{
    /**
     * Data to be composed into the view.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Action name.
     *
     * @var null
     */
    protected $name = null;

    /**
     * View path.
     *
     * @var null
     */
    protected $view = null;

    /**
     * View computed hint.
     *
     * @var null
     */
    protected $hint = null;

    /**
     * View intermediate path.
     *
     * @var null
     */
    protected $intermediatePath = null;

    public function __construct($args)
    {
        // Compute arguments: ($name, $data[]) or just ($name).
        // Refactor later. Or never :)
        if (isset($args[0]) && is_array($args[0])) {
            $this->data = $args[0];
        }

        if (isset($args[0]) && ! is_array($args[0])) {
            $name = $args[0];
        }

        if (isset($args[1]) && is_array($args[0])) {
            $this->data = $args[1];
        }

        $this->hint = $this->getHint();
        $this->intermediatePath = $this->getIntermediatePath($this->hint);
        $this->name = $name ?? $this->getActionMethod();

        // Compute view.
        $this->view = $this->hint.
                      '::'.
                      $this->intermediatePath.
                      '.Panels.'.
                      $this->name;
    }

    /**
     * Finds the view path (computed action, $name argument, or 'default').
     *
     * @return string View path.
     */
    public function findView()
    {
        if (view()->exists($this->view)) {
            return $this->view;
        }

        $default = $this->hint.
                   '::'.
                   $this->intermediatePath.
                   '.Panels.default';

        if (view()->exists($default)) {
            return $default;
        }

        // Not finding the Flame Panel creates an Exception since the remaining logic
        // cannot be processed (attributes finding, twinkles rendering, etc).
        throw FlameException::PanelNotFound(Route::getCurrentRoute()->getActionName());
    }

    /**
     * Instanciates a View object with the right view path.
     *
     * @return Illuminate\Support\Facades\View
     */
    public function makeView()
    {
        return View::make($this->findView())
                   ->with($this->data);
    }
}
