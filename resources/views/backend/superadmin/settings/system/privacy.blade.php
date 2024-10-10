
                    <div class="form-group row">
                        <div class="col-12">
                          <textarea class="form-control @error('privacy_policy') is_invalid @enderror" name="privacy_policy" id="privacy_policy" value="{{( $SystemSetting->privacy_policy)}}">{{( $SystemSetting->privacy_policy)}}</textarea>
                        </div>
                        @error('privacy_policy')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                      </div>






