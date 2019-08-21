<?php

namespace Brunocfalcao\Flame\Renderers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Brunocfalcao\Flame\Exceptions\FlameException;

class Twinkle extends Renderer
{
    public function __construct($name = null)
    {
    }

    protected function findView()
    {
        /**
         * Tries to find the hinted view.
         * If not found, tries to find the $this->name view.
         */
        $possibleView = $this->hint.
                        '::'.
                        $this->intermediatePath.
                        '.Twinkles.'.
                        $this->name;

        if (view()->exists($possibleView)) {
            return $possibleView;
        }

        if (view()->exists($this->name)) {
            return $this->name;
        }

        // Cannot continue if no view name was found for the Twinkle.
        throw FlameException::twinkleNotFound($this->name);
    }

    /**
     * Besides the data that is passed via argument, it is possible to
     * have a component controller inside the Controllers folder.
     * If there is an action as the one that the parent controller is running
     * then we should run the method and grab (if returned) the data as an array.
     * This should be then added to the data that was passed via the constructor.
     *
     * @return [type] [description]
     */
    protected function enrichData()
    {
        $action = null;

        //Compute component controller namespace (studly case!).
        $namespace = config("flame.groups.{$this->hint}.namespace").
                          '\\'.
                          str_replace('.', '\\', $this->intermediatePath).
                          '\\Controllers\\'.
                          studly_case(last(explode('.', $this->name))).
                          'Controller';

        // Identify controller action: current action or 'default'.
        if (@method_exists($namespace, $this->action)) {
            $action = $this->action;
        } elseif (@method_exists($namespace, 'default')) {
            $action = 'default';
        }

        if (is_null($action)) {
            return;
        }

        // Obtain route parameters.
        $router = app()->make('router');
        $arguments = $router->getCurrentRoute()->parameters;

        // In case the action is update or store, we need to pass the request.
        if ($action == 'update' || $action == 'store') {
            $arguments['request'] = request();
        }

        // Verify if there are extra parameters in the method that need dependency injection.
        $ref = new \ReflectionMethod($namespace, $action);

        //dd($ref, $router->getCurrentRoute()->parameters);

        $extraArguments = [];
        if (count($ref->getParameters()) > 0) {
            foreach ($ref->getParameters() as $data) {
                if (! is_null($data->getType())) {
                    // Extract both Class and variable from the namedType object ($data)
                    $parameter = $data->getName();
                    $class = $data->getType()->getName();

                    /**
                     * Elaborate an implicit binding.
                     * In case the $class is instance of Model, then retrieve the model instance.
                     * Difference is, in case it doesn't exist it will throw an error.
                     */
                    if (is_subclass_of($class, 'Illuminate\Database\Eloquent\Model')) {
                        // A Model class is present. Let's cross check the parameter
                        // with the route bindings.
                        $routeParameters = $router->getCurrentRoute()->parameters;

                        if (array_key_exists($parameter, $routeParameters)) {
                            /**
                             * We do have a route parameter that is equal to our
                             * controller function parameter. Time to obtain the model
                             * instance!
                             * 1. Get route parameter value (our model route key value).
                             * 2. Get route key name from the model.
                             * 3. Query the DB to get the Model with that key name.
                             */
                            $modelValue = $routeParameters[$parameter];

                            /*
                             * In case the parent controller in the flame already uses implicit
                             * route binding, then the parameters that arrive to the twinkle will
                             * already be model objects and not string values. Let's check that...
                             */
                            if (is_object($modelValue) && is_subclass_of($modelValue, 'Illuminate\Database\Eloquent\Model')) {
                                $extraArguments[$parameter] = $modelValue;
                            } else {
                                $routeKey = (new $class)->getRouteKeyName();
                                $modelInstance = $class::where($routeKey, $modelValue)->firstOrFail();
                                $extraArguments[$parameter] = $modelInstance;
                            }
                        }
                    } else {
                        $extraArguments[$parameter] = app()->make($class);
                    }
                }
            }
        }

        // Obtain response.
        $response = app()->call("{$namespace}@{$action}", array_merge($arguments, $extraArguments));

        // Compute response to be arrayable.
        $response = ! is_array($response) ? [$response] : $response;

        // Merge response with the current data.
        $this->data = array_merge_recursive((array) $this->data, $response);
    }
}
