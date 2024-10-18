import $ from "jquery";
import axios from "axios";
import {Controller} from '@hotwired/stimulus';

import common from '../common';

/*
 * Edit user controller
 */
export default class EditUser extends Controller {
  async connect() {
    $(".dropdown-content").css('display', 'none');
    $(document).mousedown('click.document.editUser', function (event) {
      if ($(event.target).closest(".pmodal").length === 0) {
        $('.pmodal-background').remove();
      }
    });

    const $loader = $('.loading');
    $loader.css('display', 'flex');

    await axios.get(`/api/user/${this.element.dataset.userId}`).then(response => {
      $('input[name=login]').val(response.data.login);
      $('input[name=email]').val(response.data.email);
      $('input[name=dn]').val(response?.data['ad_dn']);
      $('input[name=sam]').val(response?.data['sam_account']);
      $('input[name=firstName]').val(response.data.firstName);
      $('input[name=lastName]').val(response.data.lastName);
      $('select[name=role]').find(`option[value=${response.data.roles[0]}]`).attr('selected','selected');
      $('select[name=ad]').find(`option[value=${response.data.ad?.id}]`).attr('selected','selected');
      $('select[name=state]').find(`option[value=${response?.data?.state.id}]`).attr('selected','selected');

      let controllers = this.application.controllers.filter(controller => {
        return ['firstName', 'lastName', 'login', 'dn', 'sam','email'].includes(controller.id);
      });
      for (let controller of controllers) {
        controller.execCheck();
      }
      $('.pmodal-background, .pmodal').removeClass('hidden');
      $loader.css('display', 'none');
    });
  }

  close(event) {
    event?.stopPropagation();
    $('.pmodal-background').remove();
  }

  change(event) {
    let controller = common.getControllerByIdentifier(this, 'formValidator');
    controller.execCheck();
  }

  submit(event) {
    event.stopPropagation();

    $(this.element).find('.cancel-btn').remove();
    $(this.element).find('.create-btn').remove();
    $(this.element).find('.wait-btn').css('display', 'flex');

    let state = $(this.element).find('select[name=state]').val();
    let login = $(this.element).find('input[name=login]').val();
    let role = $(this.element).find('select[name=role]').val();
    let email = $(this.element).find('input[name=email]').val();
    let ad = $(this.element).find('select[name=ad]').val();
    let sam = $(this.element).find('input[name=sam]').val();
    let dn = $(this.element).find('input[name=dn]').val();
    let firstName = $(this.element).find('input[name=firstName]').val();
    let lastName = $(this.element).find('input[name=lastName]').val();

    axios.patch(`/api/user/${this.element.dataset.userId}`, {
      update: {
        email:email,
        login,
        role,
        stateId: state,
        adId: ad,
        adSamAccount: sam,
        adDn: dn,
        lastName: lastName,
        firstName: firstName
      }
    }).then(response => {
      let $clone = $($("#lineTemplate").html());
      $clone.attr("id", response.data.id);
      $clone.find(".row-id" ).text(response.data.id);
      $clone.find('.row-login').text(response.data.login);
      $clone.find('.row-firstName').text(response?.data.firstName);
      $clone.find('.row-lastName').text(response?.data.lastName);
      $clone.find('.row-state').text(response.data.state?.state);
      $clone.find('.row-roles').text(response?.data?.roles[0].toString());
      $clone.find('.ad').text(response?.data?.ad?.ad);
      $clone.find('.row-samAccount').text(response?.data['sam_account']);
      $clone.find('.row-adDn').text(response?.data['ad_dn']?.substring(0,20));
      $clone.find('.row-email').text(response.data.email);
      $clone.find('*[data-user-id]').attr('data-user-id', response.data.id);
      $clone.find('*[data-name]').attr('data-name', response.data.login);
      $(`table tr#${this.element.dataset.userId}`).html($clone.html());
      common.toast({
        type: common.getEnum().NOTIF_SUCCESS,
        message: 'Utilisateur mis à jour'
      });
    }).catch(err => {
      common.toast({
        type: common.getEnum().NOTIF_ERROR,
        message: 'Erreur pendant la mise à jour de l\'utilisateur'
      });
    }).finally(() => {
      this.close();
    });
  }
}