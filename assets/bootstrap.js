import { startStimulusApp } from '@symfony/stimulus-bridge';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));




import { registerReactControllerComponents } from '@symfony/ux-react';

registerReactControllerComponents(require.context(
    './react/controllers',
    true,
    /\.([jt])sx?$/));

/*import { startStimulusApp } from '@symfony/stimulus-bridge';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.(j|t)sx?$/
));
*/
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
