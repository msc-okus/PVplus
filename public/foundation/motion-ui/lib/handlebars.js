var handlebars = require('public/foundation/motion-ui/lib/handlebars');

handlebars.registerHelper('private', function(item, content) {
  if (item.access === 'public') return content.fn(this);
  else return content.inverse(this);
});

module.exports = handlebars;
