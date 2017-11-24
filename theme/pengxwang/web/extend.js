(function() {
  'use strict';

  class Accordion {
    static get debug() {
      return false;
    }
    static get defaults() {
      return {
        autoCollapse: true,
        cursorItemClass: 'active',
        featureCount: 1,
        itemsSelector: 'li:not(:first-child)',
        sectionSelector: 'ul',
        triggerSelector: 'button.accordion',
      };
    }
    static toPrefix(context) {
      switch (context) {
        case 'event': return 'hlfac';
        case 'data': return 'hlf-ac';
        case 'class': return 'ac';
        case 'var': return 'ac';
        default: return 'hlf-ac';
      }
    }
    init() {
      this._sections = [];
      Array.from(this.element.querySelectorAll(this.sectionSelector))
        .forEach(this._setUpSection);
    }
    deinit() {
      this._sections.forEach(this._tearDownSection);
      this._sections = [];
    }
    _onTriggerClick(event) {
      let section = this._sections.find(section => section.triggerElement === event.currentTarget);
      this._toggleSectionFolding(section);
    }
    _setUpSection(sectionElement) {
      let itemElements = Array.from(sectionElement.querySelectorAll(this.itemsSelector));
      let section = {
        hasCursor: itemElements.some(el => el.classList.contains(this.cursorItemClass)),
        isFolded: false,
        itemElements,
        sectionElement,
        triggerElement: sectionElement.querySelector(this.triggerSelector),
      };
      this._sections.push(section);
      this._toggleSectionFolding(section, !section.hasCursor);
      this._toggleSectionEventListeners(true, section);
    }
    _tearDownSection(section) {
      this._toggleSectionEventListeners(false, section);
    }
    _toggleSectionFolding(section, folded) {
      const { hasCursor, isFolded } = section;
      if (hasCursor && folded) { return; }
      if (folded == null) { folded = !isFolded; }
      else if (isFolded === folded) { return; }
      if (this.autoCollapse && !folded) {
        this._sections.filter(s => s !== section)
          .forEach(s => this._toggleSectionFolding(s, true));
      }
      let { itemElements, sectionElement } = section;
      itemElements.slice(this.featureCount)
        .forEach(el => el.style.display = folded ? 'none' : 'block');
      sectionElement.classList.toggle(this.className('folded'), folded);
      section.isFolded = folded;
    }
    _toggleSectionEventListeners(on, section) {
      let { triggerElement } = section;
      this.toggleEventListeners(on, {
        'click': this._onTriggerClick,
      }, triggerElement);
    }
  }

  HLF.buildExtension(Accordion, {
    autoBind: true,
    compactOptions: true,
    mixinNames: ['event'],
  });

  class Slideshow {
    static get debug() {
      return false;
    }
    static get defaults() {
      return {
        currentSlideClass: 'current',
        highlightClass: 'highlighted',
        highlightDuration: 500,
        selectors: {
          nextElement: 'button.next',
          previousElement: 'button.previous',
          slideElements: '.slide',
          slidesElement: '.slides',
        },
      };
    }
    static toPrefix(context) {
      switch (context) {
        case 'event': return 'hlfss';
        case 'data': return 'hlf-ss';
        case 'class': return 'ss';
        case 'var': return 'ss';
        default: return 'hlf-ss';
      }
    }
    init() {
      this.slideElements = Array.from(this.slideElements); // TODO
      this.slidesElement.style.position = 'relative';
      this._toggleEventListeners(true);
      this.changeSlide(0);
    }
    deinit() {
      this._toggleEventListeners(false);
    }
    get currentSlideElement() {
      if (!this.slideElements) { return null; } // TODO
      return this.slideElements[this.currentSlideIndex];
    }
    changeSlide(index, { animated } = { animated: true }) {
      if (index < 0 || index >= this.slideElements.length) { return false; }
      if (this.currentSlideElement) {
        this.currentSlideElement.classList.remove(this.currentSlideClass);
      }
      this.currentSlideIndex = index;
      this.currentSlideElement.classList.add(this.currentSlideClass);
      if (animated) {
        this.currentSlideElement.scrollIntoView({ behavior: 'smooth' });
        this._isAnimatingScroll = true;
      }
      if (this.nextElement instanceof HTMLButtonElement) {
        this.nextElement.disabled = index === (this.slideElements.length - 1);
      }
      if (this.previousElement instanceof HTMLButtonElement) {
        this.previousElement.disabled = index === 0;
      }
      this.dispatchCustomEvent('slidechange', { element: this.currentSlideElement, index });
      return true;
    }
    _highlightElement(element) {
      if (element.classList.contains(this.highlightClass)) { return; }
      element.classList.add(this.highlightClass);
      setTimeout(() => {
        element.classList.remove(this.highlightClass);
      }, this.highlightDuration);
    }
    _onKeyDown(event) {
      const leftArrow = 37, rightArrow = 39;
      switch (event.keyCode) {
        case leftArrow:
          this.setTimeout('_keyDownTimeout', 96, () => {
            if (this.changeSlide(this.currentSlideIndex - 1)) {
              this._highlightElement(this.previousElement);
            }
          });
          event.preventDefault();
          return false;
        case rightArrow:
          this.setTimeout('_keyDownTimeout', 96, () => {
            if (this.changeSlide(this.currentSlideIndex + 1)) {
              this._highlightElement(this.nextElement);
            }
          });
          event.preventDefault();
          return false;
        default: break;
      }
    }
    _onNextClick(event) {
      this.changeSlide(this.currentSlideIndex + 1);
    }
    _onPreviousClick(event) {
      this.changeSlide(this.currentSlideIndex - 1);
    }
    _onSlidesClick(event) {
      const midX = this.slidesElement.offsetWidth / 2;
      if (event.offsetX < midX) {
        if (this.changeSlide(this.currentSlideIndex - 1)) {
          this._highlightElement(this.previousElement);
        }
      } else {
        if (this.changeSlide(this.currentSlideIndex + 1)) {
          this._highlightElement(this.nextElement);
        }
      }
    }
    _onSlidesScroll(event) {
      if (this._isAnimatingScroll) {
        this._isAnimatingScroll = false;
        return;
      }
      this.setTimeout('_scrollTimeout', 96, () => {
        let nextIndex;
        for (let i = 0, l = this.slideElements.length; i < l; i++) {
          let slideElement = this.slideElements[i];
          if (slideElement.offsetLeft >= this.slidesElement.scrollLeft) {
            nextIndex = i;
            break;
          }
        }
        this.changeSlide(nextIndex, {
          animated: !('scrollSnapType' in this.slidesElement.style),
        });
      });
    }
    _toggleEventListeners(on) {
      this.toggleEventListeners(on, {
        keydown: this._onKeyDown,
      }, document.body);
      this.toggleEventListeners(on, {
        click: this._onNextClick,
      }, this.nextElement);
      this.toggleEventListeners(on, {
        click: this._onPreviousClick,
      }, this.previousElement);
      this.toggleEventListeners(on, {
        click: this._onSlidesClick,
        scroll: this._onSlidesScroll,
      }, this.slidesElement);
    }
  }

  HLF.buildExtension(Slideshow, {
    autoBind: true,
    autoSelect: true,
    compactOptions: true,
    mixinNames: ['event'],
  });

  document.addEventListener('DOMContentLoaded', () => {
    let navElement = document.querySelector('#index > nav');
    let accordion = Accordion.extend(navElement);
    let slideshowElement = document.querySelector('#post .slideshow');
    if (slideshowElement !== null) {
      let slideshow = Slideshow.extend(slideshowElement);
      let counterElement = slideshowElement.querySelector('.counter');
      slideshowElement.addEventListener('hlfssslidechange', (event) => {
        let { element, index } = event.detail;
        counterElement.textContent = `${index + 1}`;
      });
    }
    const { Tip } = HLF;
    let navTip = Tip.extend(navElement.querySelectorAll('[title]'), {
      snapTo: 'y', contextElement: navElement
    });
    let footerElement = document.querySelector('#index > footer');
    let footerTip = Tip.extend(footerElement.querySelectorAll('[title]'), {
      snapTo: 'x', contextElement: footerElement
    });
    Array.from(document.querySelectorAll('#post section')).forEach((sectionElement) => {
      if (sectionElement.classList.contains('external')) {
        let externalTip = Tip.extend(sectionElement.querySelectorAll('[title]'), {
          snapTo: 'y', contextElement: sectionElement
        });
        return;
      }
      let footerTip = Tip.extend(sectionElement.querySelectorAll('[title]'), {
        snapTo: 'x', contextElement: sectionElement
      });
    });
  });

}());
