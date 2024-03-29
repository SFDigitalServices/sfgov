// This is copied from maxlength contrib module code.
// The changes in this allow for it to be attached to the field title.
(function ($, Drupal) {
  // Make everything local
  let ml
  ml = ml || {} // eslint-disable-line
  ml.options = ml.options || {}

  Drupal.behaviors.maxlength = {
    attach (context) {
      const $context = $(context)

      if (Drupal.ckeditor !== undefined) {
        ml.ckeditor()
      }

      $context.find('.maxlength').filter(':input').once('maxlength')
        .each(function () {
          const options = {}
          const $this = $(this)
          options.counterText = $this.attr('maxlength_js_label')
          if ($this.hasClass('maxlength_js_enforce')) {
            options.enforce = true
          }
          $this.charCount(options)
        })
    },
    detach (context) {
      const $context = $(context)
      $context.find('.maxlength').removeOnce('data-maxlength').each(function () {
        $(this).charCount({
          action: 'detach'
        })
      })
    }
  }

  /**
   * Code below is based on:
   *   Character Count Plugin - jQuery plugin
   *   Dynamic character count for text areas and input fields
   *   written by Alen Grakalic
   *   https://gist.github.com/Fabax/4724890
   *
   *  @param obj
   *    a jQuery object for input elements
   *  @param options
   *    an array of options.
   *  @param count
   *    In case obj.val() wouldn't return the text to count, this should
   *    be passed with the number of characters.
   *  @param wysiwyg
   *  @param getter
   *  @param setter
   */
  ml.calculate = function (obj, options, count, wysiwyg, getter, setter) {
    const counter = $('#' + obj.attr('id') + '-' + options.css)
    const limit = parseInt(obj.attr('data-maxlength'))

    if (count === undefined) {
      count = ml.strip_tags(obj.val()).length
    }

    let available = limit - count

    if (available <= options.warning) {
      counter.addClass(options.cssWarning)
    }
    else {
      counter.removeClass(options.cssWarning)
    }

    if (available < 0) {
      counter.addClass(options.cssExceeded)
      // Trim text.
      if (options.enforce) {
        if (wysiwyg !== undefined) {
          if (typeof ml[getter] == 'function' && typeof ml[setter] == 'function') {
            const newHtml = ml.truncate_html(ml[getter](wysiwyg), limit)
            ml[setter](wysiwyg, newHtml)
            count = ml.strip_tags(newHtml).length
          }
        }
        else {
          obj.val(ml.truncate_html(obj.val(), limit))
          // Recalculate text length
          count = ml.strip_tags(obj.val()).length
        }
      }
      available = limit - count
    }
    else {
      counter.removeClass(options.cssExceeded)
    }

    counter.html(options.counterText.replace('@limit', limit).replace('@remaining', available).replace('@count', count))
  }

  /**
   * Replaces line ending with to chars, because PHP-calculation counts with two chars
   * as two characters.
   *
   * @see http://www.sitepoint.com/blogs/2004/02/16/line-endings-in-javascript/
   */
  ml.twochar_lineending = function (str) {
    return str.replace(/(\r\n|\r|\n)/g, '\r')
  }

  ml.strip_tags = function (input, allowed) {
    // Remove all newlines, spaces and tabs from the beginning and end of html.
    input = $.trim(input)
    // making the line-endings with two chars
    input = ml.twochar_lineending(input)
    // input = input.split(' ').join('');
    // Strips HTML and PHP tags from a string
    allowed = (((allowed || '') + '')
      .toLowerCase()
      .match(/<[a-z][a-z0-9]*>/g) || [])
      .join('') // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
    const tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi
    const commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi
    input = input.replace(commentsAndPhpTags, '').replace(tags, ($0, $1) => {
      return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '' // eslint-disable-line
    })

    // Replace all html entities with a single character (#) placeholder.
    return input.replace(/&([a-z]+);/g, '#')
  }

  /**
   * Cuts a html text up to limit characters. Still experimental.
   */
  ml.truncate_html = function (text, limit) {
    // The html result after cut.
    let resultHtml = ''
    // The text result, that will actually used when counting characters.
    let resultText = ''
    // A stack that will keep the tags that are open at a given time.
    const tagsOpen = []
    // making the line-endings with two chars
    text = ml.twochar_lineending(text)
    while (resultText.length < limit && text.length > 0) {
      switch (text.charAt(0)) {
        case '<': {
          if (text.charAt(1) !== '/') {
            let tagName = ''
            let tagNameCompleted = false
            while (text.charAt(0) !== '>' && text.length > 0) {
              const firstChar = text.charAt(0).toString()
              // Until the tag is closed, we do not append anything
              // to the visible text, only to the html.
              resultHtml += firstChar
              // Also, check if we have a valid tag name.
              if (!tagNameCompleted && firstChar === ' ') {
                // We have the tag name, so push it into the stack.
                tagNameCompleted = true
                tagsOpen.push(tagName)
              }
              // Check if we are still in the tag name.
              if (!tagNameCompleted && firstChar !== '<') {
                tagName += firstChar
              }
              // If we have the combination "/>" it means that the tag
              // is closed, so remove it from the open tags stack.
              if (firstChar === '/' && text.length > 1 && text.charAt(1) === '>') {
                tagsOpen.pop()
              }
              // Done with this char, remove it from the original text.
              text = text.substring(1)
            }
            if (!tagNameCompleted) {
              // In this case we have a tag like "<strong>some text</strong> so
              // we did not have any attributes in the tag, but still, the tag
              // has to be marked as open.
              tagsOpen.push(tagName)
            }
            // We are here, then the tag is closed, so just remove the
            // remaining ">" character.
            if (text.length > 0) {
              resultHtml += text.charAt(0).toString()
            }
          }
          else {
            // In this case, we have an ending tag.
            // The name of the ending tag should match the last open tag,
            // otherwise, something is wrong with th html text.
            let tagName = ''
            while (text.charAt(0) !== '>' && text.length > 0) {
              const firstChar = text.charAt(0).toString()
              if (firstChar !== '<' && firstChar !== '/') {
                tagName += firstChar
              }
              resultHtml += firstChar
              text = text.substring(1)
            }
            if (text.length > 0) {
              resultHtml = resultHtml + text.charAt(0).toString()
            }
            // Pop the last element from the tags stack and compare it with
            // the tag name.
            const expectedTagName = tagsOpen.pop()
            if (expectedTagName !== tagName) {
              // Should throw an exception, but for the moment just alert.
              alert('Expected end tag: ' + expectedTagName + '; Found end tag: ' + tagName) // eslint-disable-line
            }
          }
          break
        }
        case '&': {
          // Don't truncate in the middle of an html entity count it as 1.
          const entities = text.match(/&([a-z]+);/g)
          if (entities) {
            const nextEntity = entities[0]
            resultHtml += nextEntity
            resultText += '#'
            text = text.slice(nextEntity.length - 1)
          }
          break
        }
        default: {
          // In this case, we have a character that should also count for the
          // limit, so append it to both, the html and text result.
          const firstChar = text.charAt(0).toString()
          resultHtml += firstChar
          resultText += firstChar
          break
        }
      }
      // Remove the first character, it did its job.
      text = text.substring(1)
    }
    // Restore the open tags that were not closed. This happens when the text
    // got truncated in the middle of one or more html tags.
    let tag = ''
    while (tag = tagsOpen.pop()) { // eslint-disable-line
      resultHtml += '</' + tag + '>'
    }
    return resultHtml
  }

  $.fn.charCount = function (options) {
    // default configuration properties
    const defaults = {
      warning: 10,
      css: 'counter',
      counterElement: 'span',
      cssWarning: 'warning',
      cssExceeded: 'error',
      counterText: Drupal.t('Content limited to @limit characters, remaining: <strong>@remaining</strong>'),
      action: 'attach',
      enforce: false
    }

    options = $.extend(defaults, options)
    ml.options[$(this).attr('id')] = options

    if (options.action === 'detach') {
      $(this).removeOnce('data-maxlength')
      $('#' + $(this).attr('id') + '-' + options.css).remove()
      delete ml.options[$(this).attr('id')]
      return 'removed'
    }

    const sanitizedId = ($(this).attr('id') + '-' + options.css).replace(/[^0-9a-z-_]/gi, '')
    const counterElement = $('<' + options.counterElement + ' id="' + sanitizedId + '" class="' + options.css + '"></' + options.counterElement + '>')

    // Use there is a description element use it to place the counterElement.
    // var describedBy = $(this).attr('aria-describedby');
    const identifiedBy = $(this).attr('id')
    if (identifiedBy && $('#' + identifiedBy).length) {
      // $('#' + describedBy).before(counterElement);
      $('label[for="' + identifiedBy + '"]').append(counterElement)
    }
    else if ($(this).next('div.grippie').length) {
      $(this).next('div.grippie').append(counterElement)
    }
    else if ($(this).next('div.cke_chrome').length) {
      $(this).next('div.cke_chrome').append(counterElement)
    }
    else {
      $(this).append(counterElement)
    }

    ml.calculate($(this), options)
    $(this).keyup(function () {
      ml.calculate($(this), options)
    })
    $(this).change(function () {
      ml.calculate($(this), options)
    })
  }

  ml.ckeditorOnce = false

  /**
   * Integrate with ckEditor
   * Detect changes on editors and invoke ml.calculate()
   */
  ml.ckeditor = function () {
    // Since Drupal.attachBehaviors() can be called more than once, and
    // ml.ckeditor() is being called in maxlength behavior, only run this once.
    if (!ml.ckeditorOnce) {
      ml.ckeditorOnce = true
      CKEDITOR.on('instanceReady', e => { // eslint-disable-line
        const editor = $('#' + e.editor.name + '.maxlength')
        if (editor.length === 1) {
          ml.options[e.editor.element.getId()].enforce = !!editor.hasClass('maxlength_js_enforce')
          // Add the events on the editor.
          e.editor.on('key', e => {
            setTimeout(() => {
              ml.ckeditorChange(e)
            }, 100) // eslint-disable-line
          })
          e.editor.on('paste', e => {
            setTimeout(() => {
              ml.ckeditorChange(e)
            }, 500) // eslint-disable-line
          })
          e.editor.on('elementsPathUpdate', e => {
            setTimeout(() => {
              ml.ckeditorChange(e)
            }, 100) // eslint-disable-line
          })
        }
      })
    }
  }
  // Invoke ml.calculate() for editor
  ml.ckeditorChange = function (e) {
    // Clone to avoid changing defaults
    const options = $.extend({}, ml.options[e.editor.element.getId()])
    ml.calculate($('#' + e.editor.element.getId()), options, ml.strip_tags(ml.ckeditorGetData(e)).length, e, 'ckeditorGetData', 'ckeditorSetData')
  }

  // Gets the data from the ckeditor.
  ml.ckeditorGetData = function (e) {
    return e.editor.getData()
  }

  // Sets the data into a ckeditor.
  ml.ckeditorSetData = function (e, data) {
    // WYSIWYG can convert '\r\n' to '\n' and insert '\n' after some tags, this
    // can result in circular changes as what is attempted to be inserted and
    // what is actually inserted is different. We save the last inserted value
    // on the editor to stop this issue.
    if (e.editor.mlLastBeforeInsert !== e.editor.getData()) {
      e.editor.mlLastBeforeInsert = e.editor.getData()
      // Calling setData() will place the cursor at the beginning, so we need to
      // implement a callback to place it at the end, which is where the text is
      // being truncated.
      e.editor.setData(data, {
        callback () {
          e.editor.focus()
          const range = e.editor.createRange()
          range.moveToElementEditablePosition(e.editor.editable(), true)
          e.editor.getSelection().selectRanges([range])
        }
      })
    }
  }
})(jQuery, Drupal)
